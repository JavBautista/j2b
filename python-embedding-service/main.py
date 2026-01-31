# python-embedding-service/main.py
"""
Microservicio FastAPI para embeddings y búsqueda semántica de productos
J2Biznes - Sistema Multi-Tenant (filtro por shop_id)
"""
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional
import os
from dotenv import load_dotenv

# Cargar variables de entorno
load_dotenv()

app = FastAPI(
    title="J2Biznes Embedding Service",
    version="1.0.0",
    description="Microservicio de embeddings para Chat IA - Multi-Tenant"
)

# CORS para Laravel
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://j2b.test", "http://localhost", "http://127.0.0.1"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ============================================
# MODELOS PYDANTIC (Validación de datos)
# ============================================

class EmbedRequest(BaseModel):
    """Request para generar embedding de un texto"""
    text: str

class EmbedResponse(BaseModel):
    """Response con el vector generado"""
    vector: List[float]
    dimensions: int

class SearchSemanticRequest(BaseModel):
    """Request para búsqueda semántica - MULTI-TENANT"""
    query: str
    limit: int = 5
    shop_id: int  # REQUERIDO - Filtro multi-tenant
    user_level: int = 1  # Nivel de precio (1=retail, 2=wholesale, 3=premium)
    item_type: Optional[str] = None  # "product", "service", o None para ambos

class CatalogItem(BaseModel):
    """Modelo unificado para productos y servicios"""
    id: int
    type: str = "product"  # "product" o "service"
    key: Optional[str] = ""  # SKU/código (solo productos)
    name: str
    price: float
    stock: Optional[float] = None  # Solo productos tienen stock
    category: str
    score: float  # Similitud semántica (0-1)

# Alias para compatibilidad
Product = CatalogItem

class SearchSemanticResponse(BaseModel):
    """Response de búsqueda semántica"""
    found: bool
    products: List[CatalogItem]  # Incluye productos y servicios
    query_time_ms: float
    shop_id: int  # Confirmar qué tienda se consultó

# ============================================
# ENDPOINTS
# ============================================

@app.get("/")
def health_check():
    """
    Health check - Verificar que el servicio está funcionando
    """
    return {
        "status": "ok",
        "service": "J2Biznes Embedding Service",
        "version": "1.0.0",
        "model": os.getenv("EMBEDDING_MODEL", "all-MiniLM-L6-v2"),
        "multi_tenant": True
    }

@app.post("/embed", response_model=EmbedResponse)
def generate_embedding(request: EmbedRequest):
    """
    Genera embedding (vector) de un texto

    Ejemplo:
    POST /embed
    {"text": "cable hdmi 2 metros"}

    Returns:
    {"vector": [0.12, 0.45, ..., 0.89], "dimensions": 384}
    """
    try:
        from models import embedding_model

        vector = embedding_model.encode(request.text).tolist()

        return EmbedResponse(
            vector=vector,
            dimensions=len(vector)
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/search/semantic", response_model=SearchSemanticResponse)
async def search_semantic(request: SearchSemanticRequest):
    """
    Búsqueda semántica MULTI-TENANT con threshold dinámico

    IMPORTANTE: shop_id es REQUERIDO para filtrar por tienda.

    Ejemplo:
    POST /search/semantic
    {"query": "cables", "limit": 5, "shop_id": 26, "user_level": 1}

    Returns:
    {
        "found": true,
        "products": [...],
        "query_time_ms": 127.45,
        "shop_id": 26
    }
    """
    try:
        from models import embedding_model
        from qdrant_client_wrapper import search_products
        from search_optimizer import SearchOptimizer
        import time

        start_time = time.time()

        # Inicializar optimizador
        optimizer = SearchOptimizer()

        # 1. Preprocesar query (expandir si es necesario)
        query_processed = optimizer.preprocess_query(request.query)

        # 2. Obtener threshold dinámico basado en longitud del query
        threshold = optimizer.get_threshold_for_query(request.query)

        type_filter = f", tipo: {request.item_type}" if request.item_type else ""
        print(f"\n🔍 Query: '{request.query}' (shop_id: {request.shop_id}{type_filter})")
        print(f"   Procesado: '{query_processed}'")
        print(f"   Threshold: {threshold:.2f}")

        # 3. Generar embedding del query procesado
        query_vector = embedding_model.encode(query_processed).tolist()

        # 4. Buscar con filtro shop_id (MULTI-TENANT) y tipo opcional
        results = search_products(
            query_vector=query_vector,
            limit=request.limit * 3,  # Buscamos más para filtrar después
            shop_id=request.shop_id,  # FILTRO MULTI-TENANT
            user_level=request.user_level,
            item_type=request.item_type  # Filtro por tipo (product/service/None)
        )

        # 5. Aplicar boost y filtrar con threshold dinámico
        boosted_results = []
        for product in results:
            # Calcular boost basado en match exacto
            boost = optimizer.should_boost_result(
                request.query,
                product.get('name', ''),
                product.get('category', '')
            )

            # Score final con boost
            final_score = min(product['score'] * boost, 1.0)

            # Solo incluir si supera el threshold dinámico
            if final_score >= threshold:
                product['score'] = final_score
                boosted_results.append(product)

        # 6. Re-rankear y ordenar por score
        boosted_results = optimizer.rerank_results(boosted_results, request.query)
        boosted_results.sort(key=lambda x: x['score'], reverse=True)

        # 7. Limitar a la cantidad solicitada
        final_results = boosted_results[:request.limit]

        elapsed = (time.time() - start_time) * 1000

        # Log para debug
        print(f"   Candidatos: {len(results)}")
        print(f"   Después de boost: {len(boosted_results)}")
        print(f"   Resultados finales: {len(final_results)}")
        if final_results:
            print(f"   Scores: {[round(p['score'], 3) for p in final_results[:3]]}")

        return SearchSemanticResponse(
            found=len(final_results) > 0,
            products=final_results,
            query_time_ms=round(elapsed, 2),
            shop_id=request.shop_id
        )

    except Exception as e:
        print(f"❌ Error en búsqueda: {e}")
        raise HTTPException(status_code=500, detail=str(e))

# ============================================
# ENDPOINTS DE INDEXACIÓN Y ESTADÍSTICAS
# ============================================

class IndexRequest(BaseModel):
    """Request para indexar productos/servicios"""
    shop_id: int

@app.get("/stats/shop/{shop_id}")
def get_shop_stats(shop_id: int):
    """
    Obtener conteos de productos y servicios indexados para una tienda

    GET /stats/shop/26
    Returns: {"products": 53, "services": 21}
    """
    try:
        from qdrant_client_wrapper import count_by_shop
        counts = count_by_shop(shop_id)
        return counts
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/index/products")
def index_products(request: IndexRequest):
    """
    Indexar todos los productos activos de una tienda

    POST /index/products
    {"shop_id": 26}
    """
    try:
        import pymysql
        import pymysql.cursors
        from models import embedding_model
        from qdrant_client_wrapper import delete_products_by_shop, insert_product

        shop_id = request.shop_id
        print(f"\n📦 Indexando productos de shop_id={shop_id}...")

        # Conectar a MySQL
        conn = pymysql.connect(
            host=os.getenv("MYSQL_HOST", "127.0.0.1"),
            port=int(os.getenv("MYSQL_PORT", 3306)),
            user=os.getenv("MYSQL_USER", "root"),
            password=os.getenv("MYSQL_PASSWORD", ""),
            database=os.getenv("MYSQL_DATABASE", "j2b"),
            cursorclass=pymysql.cursors.DictCursor
        )
        cursor = conn.cursor()

        # Obtener productos activos
        cursor.execute("""
            SELECT p.id, p.key, p.name, p.description, p.cost, p.retail, p.wholesale,
                   p.wholesale_premium, p.stock, p.shop_id,
                   COALESCE(c.name, 'Sin categoría') as category
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.shop_id = %s AND p.active = 1
        """, (shop_id,))
        products = cursor.fetchall()

        # Eliminar productos anteriores de esta tienda
        delete_products_by_shop(shop_id)

        # Indexar cada producto
        indexed = 0
        errors = 0
        for product in products:
            try:
                # Crear texto para embedding
                text = f"{product['name']} {product['description'] or ''} {product['category']}"

                # Generar embedding
                vector = embedding_model.encode(text).tolist()

                # Preparar payload
                payload = {
                    "product_id": product['id'],
                    "shop_id": product['shop_id'],
                    "key": product['key'] or "",
                    "name": product['name'],
                    "category": product['category'],
                    "retail": float(product['retail'] or 0),
                    "wholesale": float(product['wholesale'] or 0),
                    "wholesale_premium": float(product['wholesale_premium'] or 0),
                    "stock": float(product['stock'] or 0)
                }

                # Insertar en Qdrant
                insert_product(product['id'], vector, payload)
                indexed += 1
            except Exception as e:
                print(f"   ❌ Error con producto {product['id']}: {e}")
                errors += 1

        cursor.close()
        conn.close()

        print(f"✅ Productos indexados: {indexed}, errores: {errors}")
        return {"indexed": indexed, "errors": errors}

    except Exception as e:
        print(f"❌ Error indexando productos: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/index/services")
def index_services(request: IndexRequest):
    """
    Indexar todos los servicios activos de una tienda

    POST /index/services
    {"shop_id": 26}
    """
    try:
        import pymysql
        import pymysql.cursors
        from models import embedding_model
        from qdrant_client_wrapper import delete_services_by_shop, insert_service

        shop_id = request.shop_id
        print(f"\n🔧 Indexando servicios de shop_id={shop_id}...")

        # Conectar a MySQL
        conn = pymysql.connect(
            host=os.getenv("MYSQL_HOST", "127.0.0.1"),
            port=int(os.getenv("MYSQL_PORT", 3306)),
            user=os.getenv("MYSQL_USER", "root"),
            password=os.getenv("MYSQL_PASSWORD", ""),
            database=os.getenv("MYSQL_DATABASE", "j2b"),
            cursorclass=pymysql.cursors.DictCursor
        )
        cursor = conn.cursor()

        # Obtener servicios activos
        cursor.execute("""
            SELECT id, name, description, price, shop_id
            FROM services
            WHERE shop_id = %s AND active = 1
        """, (shop_id,))
        services = cursor.fetchall()

        # Eliminar servicios anteriores de esta tienda
        delete_services_by_shop(shop_id)

        # Indexar cada servicio
        indexed = 0
        errors = 0
        for service in services:
            try:
                # Crear texto para embedding
                text = f"{service['name']} {service['description'] or ''} servicio"

                # Generar embedding
                vector = embedding_model.encode(text).tolist()

                # Preparar payload
                payload = {
                    "service_id": service['id'],
                    "shop_id": service['shop_id'],
                    "name": service['name'],
                    "price": float(service['price'] or 0),
                    "category": "Servicios"
                }

                # Insertar en Qdrant
                insert_service(service['id'], vector, payload)
                indexed += 1
            except Exception as e:
                print(f"   ❌ Error con servicio {service['id']}: {e}")
                errors += 1

        cursor.close()
        conn.close()

        print(f"✅ Servicios indexados: {indexed}, errores: {errors}")
        return {"indexed": indexed, "errors": errors}

    except Exception as e:
        print(f"❌ Error indexando servicios: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/index/catalog")
def index_catalog(request: IndexRequest):
    """
    Indexar catálogo completo (productos + servicios) de una tienda

    POST /index/catalog
    {"shop_id": 26}
    """
    try:
        # Indexar productos
        products_result = index_products(request)

        # Indexar servicios
        services_result = index_services(request)

        return {
            "products": products_result["indexed"],
            "services": services_result["indexed"],
            "errors": products_result.get("errors", 0) + services_result.get("errors", 0)
        }

    except Exception as e:
        print(f"❌ Error indexando catálogo: {e}")
        raise HTTPException(status_code=500, detail=str(e))

# ============================================
# STARTUP EVENT
# ============================================

@app.on_event("startup")
async def startup_event():
    """
    Se ejecuta al iniciar el servicio
    Carga el modelo de embeddings y crea colección en Qdrant
    """
    print("\n" + "="*60)
    print("🚀 Iniciando J2Biznes Embedding Service...")
    print("   (Sistema Multi-Tenant)")
    print("="*60)

    # 1. Cargar modelo de embeddings
    print("\n📦 Cargando modelo de embeddings...")
    from models import embedding_model
    test_vector = embedding_model.encode("test")
    print(f"✅ Modelo cargado correctamente ({len(test_vector)} dimensiones)")

    # 2. Crear colección en Qdrant (si no existe)
    print("\n🗄️  Configurando base de datos vectorial (Qdrant)...")
    from qdrant_client_wrapper import create_collection
    create_collection()

    print("\n" + "="*60)
    print("✅ Servicio listo para recibir peticiones")
    print(f"📍 URL: http://localhost:{os.getenv('API_PORT', 8001)}")
    print("⚠️  Recuerda: shop_id es REQUERIDO en /search/semantic")
    print("="*60 + "\n")

# ============================================
# EJECUTAR
# ============================================

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("API_PORT", 8001))
    uvicorn.run(app, host="0.0.0.0", port=port)
