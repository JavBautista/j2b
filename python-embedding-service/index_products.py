#!/usr/bin/env python3
"""
Script para indexar productos de MySQL en Qdrant
J2Biznes - Sistema Multi-Tenant

Este script:
1. Lee todos los productos activos de la tabla 'products'
2. Genera embeddings de sus descripciones
3. Los guarda en Qdrant con shop_id para filtro multi-tenant
"""
import pymysql
import os
import sys
from dotenv import load_dotenv
from models import embedding_model
from qdrant_client_wrapper import insert_product, COLLECTION_NAME, qdrant, delete_products_by_shop

# Cargar variables de entorno
load_dotenv()

def connect_mysql():
    """
    Conectar a base de datos MySQL
    """
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
    """
    Obtener productos activos de la BD

    Args:
        connection: Conexión MySQL
        shop_id: Si se especifica, solo indexa productos de esa tienda.
                 Si es None, indexa TODOS los productos de TODAS las tiendas.

    Returns:
        Lista de productos
    """
    try:
        with connection.cursor() as cursor:
            # Query adaptado a estructura de J2B
            # Tabla: products
            # Relación: categories (category_id -> categories.id)
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

            # Filtrar por tienda si se especifica
            if shop_id is not None:
                query += f" AND p.shop_id = {shop_id}"

            query += " ORDER BY p.shop_id, p.id"

            cursor.execute(query)
            products = cursor.fetchall()
            print(f"✅ Encontrados {len(products)} productos activos")
            return products
    except Exception as e:
        print(f"❌ Error obteniendo productos: {e}")
        sys.exit(1)

def index_products(products, clear_shop_first: bool = False):
    """
    Indexar productos en Qdrant

    Args:
        products: Lista de productos de MySQL
        clear_shop_first: Si True, elimina productos de cada tienda antes de indexar
    """
    print("\n" + "="*60)
    print("🚀 Iniciando indexación de productos (Multi-Tenant)...")
    print("="*60)

    indexed = 0
    errors = 0
    shops_processed = set()

    for i, product in enumerate(products, 1):
        try:
            shop_id = product['shop_id']

            # Opcional: Limpiar productos de la tienda antes de re-indexar
            if clear_shop_first and shop_id not in shops_processed:
                delete_products_by_shop(shop_id)
                shops_processed.add(shop_id)

            # Construir texto para embedding
            text_parts = []

            # 1. Nombre (peso principal)
            text_parts.append(product['name'])

            # 2. Key/SKU
            if product.get('key'):
                text_parts.append(product['key'])

            # 3. Código de barras
            if product.get('barcode'):
                text_parts.append(product['barcode'])

            # 4. Descripción
            if product.get('description'):
                text_parts.append(product['description'])

            # 5. Categoría
            if product.get('category_name'):
                text_parts.append(product['category_name'])

            # Unir todo en un solo texto
            text = ' '.join(text_parts)

            # Generar vector embedding (384 dimensiones)
            vector = embedding_model.encode(text).tolist()

            # Preparar payload (datos del producto + shop_id)
            payload = {
                "product_id": product['id'],
                "shop_id": product['shop_id'],  # CLAVE PARA MULTI-TENANT
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

            # Insertar en Qdrant
            insert_product(
                product_id=product['id'],
                vector=vector,
                payload=payload
            )

            indexed += 1

            # Mostrar progreso cada 50 productos
            if i % 50 == 0:
                print(f"📦 Progreso: {i}/{len(products)} productos ({(i/len(products)*100):.1f}%)")

        except Exception as e:
            errors += 1
            print(f"⚠️  Error en producto {product.get('key', 'N/A')} (ID: {product.get('id')}): {e}")
            continue

    print("\n" + "="*60)
    print("✅ Indexación completada")
    print(f"   ✓ Indexados: {indexed} productos")
    print(f"   ✓ Tiendas: {len(shops_processed) if shops_processed else 'todas'}")
    if errors > 0:
        print(f"   ⚠ Errores: {errors}")
    print("="*60)

    return indexed, errors

def verify_indexation():
    """
    Verificar que la indexación fue exitosa
    """
    try:
        collection_info = qdrant.get_collection(COLLECTION_NAME)
        print(f"\n📊 Estado de la colección '{COLLECTION_NAME}':")
        print(f"   • Vectores totales: {collection_info.vectors_count}")
        print(f"   • Puntos totales: {collection_info.points_count}")
        return collection_info.vectors_count
    except Exception as e:
        print(f"❌ Error verificando colección: {e}")
        return 0

def test_search(shop_id: int = None):
    """
    Probar búsqueda semántica con un ejemplo
    """
    print("\n" + "="*60)
    print("🧪 PRUEBA DE BÚSQUEDA SEMÁNTICA")
    print("="*60)

    test_query = "producto"

    # Usar el primer shop_id disponible si no se especifica
    if shop_id is None:
        shop_id = 1

    print(f"\n🔍 Buscando: '{test_query}' en tienda {shop_id}")

    try:
        from qdrant_client_wrapper import search_products

        # Generar embedding de la consulta
        query_vector = embedding_model.encode(test_query).tolist()

        # Buscar productos similares (con filtro multi-tenant)
        results = search_products(
            query_vector=query_vector,
            limit=5,
            shop_id=shop_id,
            user_level=1
        )

        if results:
            print(f"\n✅ Encontrados {len(results)} productos:\n")
            for i, product in enumerate(results, 1):
                print(f"{i}. [{product['key']}] {product['name']}")
                print(f"   Precio: ${product['price']:.2f} | Stock: {product['stock']}")
                print(f"   Similitud: {product['score']*100:.1f}%\n")
        else:
            print("❌ No se encontraron resultados")
            print("   (Verifica que existan productos para esa tienda)")

    except Exception as e:
        print(f"❌ Error en prueba de búsqueda: {e}")

def main():
    """
    Función principal
    """
    print("\n" + "="*60)
    print("📦 INDEXADOR DE PRODUCTOS - J2BIZNES")
    print("   Sistema Multi-Tenant (shop_id)")
    print("="*60 + "\n")

    # Verificar si se especificó un shop_id como argumento
    shop_id = None
    if len(sys.argv) > 1:
        try:
            shop_id = int(sys.argv[1])
            print(f"📍 Indexando solo tienda: shop_id = {shop_id}")
        except ValueError:
            print("⚠️  Argumento inválido. Uso: python index_products.py [shop_id]")
            print("   Si no se especifica shop_id, indexa TODAS las tiendas")

    # 1. Conectar a MySQL
    print("\n1️⃣  Conectando a base de datos...")
    connection = connect_mysql()

    # 2. Obtener productos
    print("\n2️⃣  Obteniendo productos activos...")
    products = get_products(connection, shop_id)

    if not products:
        print("⚠️  No hay productos para indexar")
        connection.close()
        return

    # 3. Indexar productos
    print(f"\n3️⃣  Indexando {len(products)} productos en Qdrant...")
    indexed, errors = index_products(products, clear_shop_first=(shop_id is not None))

    # 4. Cerrar conexión MySQL
    connection.close()

    # 5. Verificar indexación
    print("\n4️⃣  Verificando indexación...")
    total_vectors = verify_indexation()

    # 6. Prueba de búsqueda
    if total_vectors > 0:
        test_search(shop_id)

    print("\n✅ Proceso completado exitosamente\n")

if __name__ == "__main__":
    main()
