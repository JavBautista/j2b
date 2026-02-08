# python-embedding-service/qdrant_client_wrapper.py
"""
Cliente y funciones para interactuar con Qdrant (base de datos vectorial)
J2Biznes - Sistema Multi-Tenant (filtro por shop_id)

Soporta:
- Productos (type="product", IDs: 1-999999)
- Servicios (type="service", IDs: 1000000+)
- Clientes (type="client", IDs: 2000000+)
"""
from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams, PointStruct, Filter, FieldCondition, MatchValue
import os

# Cliente Qdrant
qdrant = QdrantClient(
    host=os.getenv("QDRANT_HOST", "localhost"),
    port=int(os.getenv("QDRANT_PORT", 6333))
)

COLLECTION_NAME = "j2b_productos_embeddings"  # Colección para productos y servicios

# Offsets para IDs (evita colisión entre tipos)
SERVICE_ID_OFFSET = 1000000
CLIENT_ID_OFFSET = 2000000

def create_collection():
    """
    Crear colección de productos si no existe
    """
    try:
        qdrant.get_collection(COLLECTION_NAME)
        print(f"✅ Colección '{COLLECTION_NAME}' ya existe")
    except:
        qdrant.create_collection(
            collection_name=COLLECTION_NAME,
            vectors_config=VectorParams(
                size=384,  # Dimensiones del modelo all-MiniLM-L6-v2
                distance=Distance.COSINE
            )
        )
        print(f"✅ Colección '{COLLECTION_NAME}' creada")

def insert_product(product_id: int, vector: list, payload: dict):
    """
    Insertar un producto en Qdrant

    Args:
        product_id: ID del producto (de MySQL)
        vector: Vector embedding (384 dimensiones)
        payload: Datos del producto (key, name, prices, shop_id, etc.)
    """
    # Asegurar que tiene el tipo
    payload["type"] = "product"
    payload["item_id"] = product_id  # ID original de MySQL

    qdrant.upsert(
        collection_name=COLLECTION_NAME,
        points=[
            PointStruct(
                id=product_id,  # Productos usan su ID directamente
                vector=vector,
                payload=payload
            )
        ]
    )

def insert_service(service_id: int, vector: list, payload: dict):
    """
    Insertar un servicio en Qdrant

    Args:
        service_id: ID del servicio (de MySQL)
        vector: Vector embedding (384 dimensiones)
        payload: Datos del servicio (name, price, shop_id, etc.)
    """
    # Asegurar que tiene el tipo
    payload["type"] = "service"
    payload["item_id"] = service_id  # ID original de MySQL

    # Usar offset para evitar colisión con productos
    qdrant_id = SERVICE_ID_OFFSET + service_id

    qdrant.upsert(
        collection_name=COLLECTION_NAME,
        points=[
            PointStruct(
                id=qdrant_id,
                vector=vector,
                payload=payload
            )
        ]
    )

def search_products(query_vector: list, limit: int = 5, shop_id: int = None, user_level: int = 1, item_type: str = None):
    """
    Buscar productos y/o servicios similares por vector CON FILTRO MULTI-TENANT

    Args:
        query_vector: Vector de la consulta del usuario
        limit: Número máximo de resultados
        shop_id: ID de la tienda (REQUERIDO para multi-tenant)
        user_level: Nivel del usuario (1=retail, 2=wholesale, 3=premium)
        item_type: Filtrar por tipo ("product", "service", o None para ambos)

    Returns:
        Lista de items encontrados con score de similitud
    """
    # Construir filtro por shop_id (MULTI-TENANT)
    must_conditions = []

    if shop_id is not None:
        must_conditions.append(
            FieldCondition(
                key="shop_id",
                match=MatchValue(value=shop_id)
            )
        )

    # Filtro opcional por tipo
    if item_type is not None:
        must_conditions.append(
            FieldCondition(
                key="type",
                match=MatchValue(value=item_type)
            )
        )

    query_filter = Filter(must=must_conditions) if must_conditions else None

    results = qdrant.search(
        collection_name=COLLECTION_NAME,
        query_vector=query_vector,
        query_filter=query_filter,
        limit=limit
    )

    items = []
    for result in results:
        payload = result.payload
        item_type_found = payload.get("type", "product")

        # Obtener precio según nivel del usuario y tipo de item
        if item_type_found == "service":
            # Servicios solo tienen un precio
            price = payload.get("price", 0)
        else:
            # Productos tienen precios por nivel
            if user_level == 1:
                price = payload.get("retail", 0)
            elif user_level == 2:
                price = payload.get("wholesale", 0)
            elif user_level == 3:
                price = payload.get("wholesale_premium", 0)
            else:
                price = payload.get("retail", 0)

        item = {
            "id": payload.get("item_id") or payload.get("product_id"),
            "type": item_type_found,
            "key": payload.get("key", ""),
            "name": payload.get("name", "Sin nombre"),
            "price": price,
            "category": payload.get("category", "Sin categoría"),
            "score": round(result.score, 4)
        }

        # Agregar stock solo si es producto
        if item_type_found == "product":
            item["stock"] = payload.get("stock", 0)

        items.append(item)

    return items

def delete_products_by_shop(shop_id: int):
    """
    Eliminar todos los productos de una tienda específica

    Útil para re-indexar productos de una tienda sin afectar otras.

    Args:
        shop_id: ID de la tienda
    """
    qdrant.delete(
        collection_name=COLLECTION_NAME,
        points_selector=Filter(
            must=[
                FieldCondition(
                    key="shop_id",
                    match=MatchValue(value=shop_id)
                ),
                FieldCondition(
                    key="type",
                    match=MatchValue(value="product")
                )
            ]
        )
    )
    print(f"🗑️  Productos de shop_id={shop_id} eliminados")

def delete_services_by_shop(shop_id: int):
    """
    Eliminar todos los servicios de una tienda específica

    Args:
        shop_id: ID de la tienda
    """
    qdrant.delete(
        collection_name=COLLECTION_NAME,
        points_selector=Filter(
            must=[
                FieldCondition(
                    key="shop_id",
                    match=MatchValue(value=shop_id)
                ),
                FieldCondition(
                    key="type",
                    match=MatchValue(value="service")
                )
            ]
        )
    )
    print(f"🗑️  Servicios de shop_id={shop_id} eliminados")

def insert_client(client_id: int, vector: list, payload: dict):
    """
    Insertar un cliente en Qdrant

    Args:
        client_id: ID del cliente (de MySQL)
        vector: Vector embedding (384 dimensiones)
        payload: Datos del cliente (name, company, phone, shop_id, etc.)
    """
    payload["type"] = "client"
    payload["item_id"] = client_id

    qdrant_id = CLIENT_ID_OFFSET + client_id

    qdrant.upsert(
        collection_name=COLLECTION_NAME,
        points=[
            PointStruct(
                id=qdrant_id,
                vector=vector,
                payload=payload
            )
        ]
    )

def delete_clients_by_shop(shop_id: int):
    """
    Eliminar todos los clientes de una tienda específica

    Args:
        shop_id: ID de la tienda
    """
    qdrant.delete(
        collection_name=COLLECTION_NAME,
        points_selector=Filter(
            must=[
                FieldCondition(
                    key="shop_id",
                    match=MatchValue(value=shop_id)
                ),
                FieldCondition(
                    key="type",
                    match=MatchValue(value="client")
                )
            ]
        )
    )
    print(f"🗑️  Clientes de shop_id={shop_id} eliminados")

def search_clients(query_vector: list, limit: int = 5, shop_id: int = None):
    """
    Buscar clientes similares por vector CON FILTRO MULTI-TENANT

    Args:
        query_vector: Vector de la consulta del usuario
        limit: Número máximo de resultados
        shop_id: ID de la tienda (REQUERIDO)

    Returns:
        Lista de clientes encontrados con score de similitud
    """
    must_conditions = [
        FieldCondition(key="type", match=MatchValue(value="client"))
    ]

    if shop_id is not None:
        must_conditions.append(
            FieldCondition(key="shop_id", match=MatchValue(value=shop_id))
        )

    results = qdrant.search(
        collection_name=COLLECTION_NAME,
        query_vector=query_vector,
        query_filter=Filter(must=must_conditions),
        limit=limit
    )

    clients = []
    for result in results:
        payload = result.payload
        clients.append({
            "id": payload.get("client_id"),
            "type": "client",
            "name": payload.get("name", ""),
            "company": payload.get("company", ""),
            "email": payload.get("email", ""),
            "phone": payload.get("phone", ""),
            "city": payload.get("city", ""),
            "state": payload.get("state", ""),
            "plan_name": payload.get("plan_name", ""),
            "observations": payload.get("observations", ""),
            "score": round(result.score, 4)
        })

    return clients

def delete_all_by_shop(shop_id: int):
    """
    Eliminar todos los items (productos y servicios) de una tienda

    Args:
        shop_id: ID de la tienda
    """
    qdrant.delete(
        collection_name=COLLECTION_NAME,
        points_selector=Filter(
            must=[
                FieldCondition(
                    key="shop_id",
                    match=MatchValue(value=shop_id)
                )
            ]
        )
    )
    print(f"🗑️  Catálogo completo de shop_id={shop_id} eliminado (productos + servicios)")

def get_collection_stats():
    """
    Obtener estadísticas de la colección

    Returns:
        dict con información de la colección
    """
    try:
        info = qdrant.get_collection(COLLECTION_NAME)
        return {
            "vectors_count": info.vectors_count,
            "points_count": info.points_count,
            "status": info.status
        }
    except Exception as e:
        return {"error": str(e)}

def count_by_shop(shop_id: int):
    """
    Contar productos y servicios indexados para una tienda específica

    Args:
        shop_id: ID de la tienda

    Returns:
        dict con conteos de productos y servicios
    """
    try:
        # Contar productos
        products_count = qdrant.count(
            collection_name=COLLECTION_NAME,
            count_filter=Filter(
                must=[
                    FieldCondition(key="shop_id", match=MatchValue(value=shop_id)),
                    FieldCondition(key="type", match=MatchValue(value="product"))
                ]
            )
        ).count

        # Contar servicios
        services_count = qdrant.count(
            collection_name=COLLECTION_NAME,
            count_filter=Filter(
                must=[
                    FieldCondition(key="shop_id", match=MatchValue(value=shop_id)),
                    FieldCondition(key="type", match=MatchValue(value="service"))
                ]
            )
        ).count

        # Contar clientes
        clients_count = qdrant.count(
            collection_name=COLLECTION_NAME,
            count_filter=Filter(
                must=[
                    FieldCondition(key="shop_id", match=MatchValue(value=shop_id)),
                    FieldCondition(key="type", match=MatchValue(value="client"))
                ]
            )
        ).count

        return {
            "products": products_count,
            "services": services_count,
            "clients": clients_count
        }
    except Exception as e:
        print(f"❌ Error contando items para shop_id={shop_id}: {e}")
        return {"products": 0, "services": 0}

# Crear colección al importar este módulo
try:
    create_collection()
except Exception as e:
    print(f"⚠️  No se pudo crear/verificar colección Qdrant: {e}")
    print("   (Qdrant debe estar corriendo para que esto funcione)")
