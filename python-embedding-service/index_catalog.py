#!/usr/bin/env python3
"""
Script para indexar PRODUCTOS y SERVICIOS en Qdrant
J2Biznes - Sistema Multi-Tenant

Este script:
1. Lee productos activos de la tabla 'products'
2. Lee servicios activos de la tabla 'services'
3. Genera embeddings de sus descripciones
4. Los guarda en Qdrant con shop_id y type para filtro multi-tenant

Uso:
    python index_catalog.py [shop_id] [--products-only] [--services-only]

Ejemplos:
    python index_catalog.py 26              # Indexar todo de tienda 26
    python index_catalog.py 26 --products-only   # Solo productos
    python index_catalog.py 26 --services-only   # Solo servicios
    python index_catalog.py                 # Indexar TODAS las tiendas
"""
import pymysql
import os
import sys
from dotenv import load_dotenv
from models import embedding_model
from qdrant_client_wrapper import (
    insert_product,
    insert_service,
    delete_products_by_shop,
    delete_services_by_shop,
    delete_all_by_shop,
    COLLECTION_NAME,
    qdrant
)

# Cargar variables de entorno
load_dotenv()


def connect_mysql():
    """Conectar a base de datos MySQL"""
    try:
        connection = pymysql.connect(
            host=os.getenv("MYSQL_HOST", "localhost"),
            port=int(os.getenv("MYSQL_PORT", 3306)),
            user=os.getenv("MYSQL_USER", "root"),
            password=os.getenv("MYSQL_PASSWORD"),
            database=os.getenv("MYSQL_DATABASE", "j2b"),
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        print("✅ Conectado a MySQL")
        return connection
    except Exception as e:
        print(f"❌ Error conectando a MySQL: {e}")
        sys.exit(1)


def get_products(connection, shop_id: int = None):
    """Obtener productos activos de la BD"""
    try:
        with connection.cursor() as cursor:
            query = """
                SELECT
                    p.id,
                    p.shop_id,
                    p.category_id,
                    p.key,
                    p.barcode,
                    p.name,
                    p.description,
                    p.cost,
                    p.retail,
                    p.wholesale,
                    p.wholesale_premium,
                    p.stock,
                    p.reserve,
                    p.active,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1
            """
            if shop_id is not None:
                query += f" AND p.shop_id = {shop_id}"
            query += " ORDER BY p.shop_id, p.id"

            cursor.execute(query)
            products = cursor.fetchall()
            print(f"   📦 Encontrados {len(products)} productos activos")
            return products
    except Exception as e:
        print(f"❌ Error obteniendo productos: {e}")
        return []


def get_services(connection, shop_id: int = None):
    """Obtener servicios activos de la BD"""
    try:
        with connection.cursor() as cursor:
            query = """
                SELECT
                    s.id,
                    s.shop_id,
                    s.name,
                    s.description,
                    s.price,
                    s.active
                FROM services s
                WHERE s.active = 1
            """
            if shop_id is not None:
                query += f" AND s.shop_id = {shop_id}"
            query += " ORDER BY s.shop_id, s.id"

            cursor.execute(query)
            services = cursor.fetchall()
            print(f"   🔧 Encontrados {len(services)} servicios activos")
            return services
    except Exception as e:
        print(f"❌ Error obteniendo servicios: {e}")
        return []


def index_products(products, clear_first: bool = False, shop_id: int = None):
    """Indexar productos en Qdrant"""
    if not products:
        return 0, 0

    print("\n" + "-"*50)
    print("📦 Indexando PRODUCTOS...")
    print("-"*50)

    if clear_first and shop_id is not None:
        delete_products_by_shop(shop_id)

    indexed = 0
    errors = 0

    for product in products:
        try:
            # Construir texto para embedding
            text_parts = [product['name']]
            if product.get('key'):
                text_parts.append(product['key'])
            if product.get('barcode'):
                text_parts.append(product['barcode'])
            if product.get('description'):
                text_parts.append(product['description'])
            if product.get('category_name'):
                text_parts.append(product['category_name'])

            text = ' '.join(text_parts)

            # Generar vector embedding
            vector = embedding_model.encode(text).tolist()

            # Payload
            payload = {
                "product_id": product['id'],
                "shop_id": product['shop_id'],
                "key": product.get('key', ''),
                "barcode": product.get('barcode', ''),
                "name": product['name'],
                "description": product.get('description', ''),
                "cost": float(product['cost']) if product.get('cost') else 0,
                "retail": float(product['retail']) if product.get('retail') else 0,
                "wholesale": float(product['wholesale']) if product.get('wholesale') else 0,
                "wholesale_premium": float(product.get('wholesale_premium') or 0),
                "stock": float(product['stock']) if product.get('stock') else 0,
                "reserve": float(product.get('reserve') or 0),
                "category_id": product.get('category_id'),
                "category": product.get('category_name') or "Sin categoría"
            }

            insert_product(
                product_id=product['id'],
                vector=vector,
                payload=payload
            )
            indexed += 1

        except Exception as e:
            errors += 1
            print(f"⚠️  Error en producto ID {product.get('id')}: {e}")

    print(f"   ✓ Productos indexados: {indexed}")
    if errors:
        print(f"   ⚠ Errores: {errors}")

    return indexed, errors


def index_services(services, clear_first: bool = False, shop_id: int = None):
    """Indexar servicios en Qdrant"""
    if not services:
        return 0, 0

    print("\n" + "-"*50)
    print("🔧 Indexando SERVICIOS...")
    print("-"*50)

    if clear_first and shop_id is not None:
        delete_services_by_shop(shop_id)

    indexed = 0
    errors = 0

    for service in services:
        try:
            # Construir texto para embedding
            text_parts = [service['name'], "servicio"]
            if service.get('description'):
                text_parts.append(service['description'])

            text = ' '.join(text_parts)

            # Generar vector embedding
            vector = embedding_model.encode(text).tolist()

            # Payload
            payload = {
                "service_id": service['id'],
                "shop_id": service['shop_id'],
                "name": service['name'],
                "description": service.get('description', ''),
                "price": float(service['price']) if service.get('price') else 0,
                "category": "Servicios"
            }

            insert_service(
                service_id=service['id'],
                vector=vector,
                payload=payload
            )
            indexed += 1

        except Exception as e:
            errors += 1
            print(f"⚠️  Error en servicio ID {service.get('id')}: {e}")

    print(f"   ✓ Servicios indexados: {indexed}")
    if errors:
        print(f"   ⚠ Errores: {errors}")

    return indexed, errors


def verify_indexation():
    """Verificar estado de la colección"""
    try:
        info = qdrant.get_collection(COLLECTION_NAME)
        print(f"\n📊 Estado de la colección '{COLLECTION_NAME}':")
        print(f"   • Vectores totales: {info.vectors_count}")
        print(f"   • Puntos totales: {info.points_count}")
        return info.vectors_count
    except Exception as e:
        print(f"❌ Error verificando colección: {e}")
        return 0


def test_search(shop_id: int):
    """Probar búsqueda semántica"""
    print("\n" + "="*60)
    print("🧪 PRUEBA DE BÚSQUEDA SEMÁNTICA")
    print("="*60)

    from qdrant_client_wrapper import search_products

    tests = [
        ("monitor", None),      # Busca productos y servicios
        ("reparación", None),   # Debería encontrar servicios
        ("laptop", "product"),  # Solo productos
    ]

    for query, item_type in tests:
        filter_text = f" (tipo: {item_type})" if item_type else " (todos)"
        print(f"\n🔍 Buscando: '{query}'{filter_text}")

        query_vector = embedding_model.encode(query).tolist()
        results = search_products(
            query_vector=query_vector,
            limit=3,
            shop_id=shop_id,
            user_level=1,
            item_type=item_type
        )

        if results:
            for i, item in enumerate(results, 1):
                tipo = "🔧 SERV" if item['type'] == 'service' else "📦 PROD"
                print(f"   {i}. {tipo} | {item['name']} | ${item['price']:.0f} | Sim: {item['score']*100:.0f}%")
        else:
            print("   No se encontraron resultados")


def main():
    """Función principal"""
    print("\n" + "="*60)
    print("📦 INDEXADOR DE CATÁLOGO - J2BIZNES")
    print("   Productos + Servicios | Multi-Tenant")
    print("="*60)

    # Parsear argumentos
    shop_id = None
    products_only = False
    services_only = False

    for arg in sys.argv[1:]:
        if arg == '--products-only':
            products_only = True
        elif arg == '--services-only':
            services_only = True
        elif arg.isdigit():
            shop_id = int(arg)

    if shop_id:
        print(f"\n📍 Indexando tienda: shop_id = {shop_id}")
    else:
        print("\n📍 Indexando TODAS las tiendas")

    # Conectar
    print("\n1️⃣  Conectando a base de datos...")
    connection = connect_mysql()

    # Obtener datos
    print("\n2️⃣  Obteniendo datos...")
    products = [] if services_only else get_products(connection, shop_id)
    services = [] if products_only else get_services(connection, shop_id)

    if not products and not services:
        print("⚠️  No hay datos para indexar")
        connection.close()
        return

    # Indexar
    print("\n3️⃣  Indexando en Qdrant...")
    clear_first = shop_id is not None

    prod_indexed, prod_errors = index_products(products, clear_first, shop_id)
    serv_indexed, serv_errors = index_services(services, clear_first, shop_id)

    # Cerrar conexión
    connection.close()

    # Verificar
    print("\n4️⃣  Verificando indexación...")
    verify_indexation()

    # Prueba
    if shop_id and (prod_indexed > 0 or serv_indexed > 0):
        test_search(shop_id)

    # Resumen
    print("\n" + "="*60)
    print("✅ INDEXACIÓN COMPLETADA")
    print(f"   📦 Productos: {prod_indexed}")
    print(f"   🔧 Servicios: {serv_indexed}")
    print(f"   ⚠️  Errores: {prod_errors + serv_errors}")
    print("="*60 + "\n")


if __name__ == "__main__":
    main()
