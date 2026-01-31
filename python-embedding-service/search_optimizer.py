# search_optimizer.py
"""
Optimizador de búsqueda para queries genéricos
Soluciona el problema de queries cortos vs largos
"""
import re

class SearchOptimizer:
    """Optimiza búsquedas según tipo de query"""

    @staticmethod
    def get_threshold_for_query(query: str) -> float:
        """
        Threshold dinámico basado en longitud de query

        Problema: Queries de 1 palabra tienen scores muy bajos porque
        comparan 1 token vs 15+ tokens del producto indexado.

        Solución: Threshold más permisivo para queries cortos.
        """
        words = len(query.split())

        if words == 1:
            return 0.45  # Muy permisivo para 1 palabra
        elif words == 2:
            return 0.50  # Intermedio para 2 palabras
        else:
            return 0.54  # Original para queries largos

    @staticmethod
    def should_boost_result(query: str, product_name: str, product_category: str = "") -> float:
        """
        Calcula boost si el producto contiene la palabra exacta

        Esto compensa la dilución semántica causada por indexar
        textos largos con muchos conceptos.
        """
        query_lower = query.lower().strip()
        name_lower = product_name.lower()

        # Palabras del query
        query_words = query_lower.split()

        # Boost máximo si TODAS las palabras del query están en el nombre
        all_words_match = all(word in name_lower for word in query_words)
        if all_words_match and len(query_words) == 1:
            # Para queries de 1 palabra, boost mayor si aparece al principio
            if name_lower.startswith(query_lower):
                return 1.5  # 50% boost
            else:
                return 1.3  # 30% boost
        elif all_words_match:
            return 1.2  # 20% boost para queries multi-palabra

        # Boost parcial si al menos una palabra coincide
        any_word_match = any(word in name_lower for word in query_words)
        if any_word_match:
            return 1.1  # 10% boost

        # Boost por raíz de palabra (primeras 4 letras)
        for word in query_words:
            if len(word) > 4:
                word_root = word[:4]
                if word_root in name_lower:
                    return 1.05  # 5% boost

        return 1.0  # Sin boost

    @staticmethod
    def preprocess_query(query: str) -> str:
        """
        Preprocesa query para mejorar matching

        SOLO limpia stop words genéricos (verbos de consulta, artículos, etc.)
        NO hardcodea productos/servicios - el RAG maneja eso con embeddings.

        Ejemplo: "tienes laptops? me recomiendas algunas?" → "laptops"
        """
        # Stop words genéricos - palabras sin valor semántico para búsqueda
        # Estas son palabras del IDIOMA, no de productos específicos
        stop_words = {
            # Verbos comunes de consulta
            'tienes', 'tienen', 'tiene', 'hay', 'busco', 'necesito', 'quiero',
            'puedo', 'pueden', 'podria', 'podrian', 'dame', 'dime', 'muestrame',
            'recomiendas', 'recomendas', 'recomienda', 'sugieres', 'sugiere',
            'vendes', 'venden', 'vende', 'ofreces', 'ofrecen', 'cuentan',
            # Artículos y pronombres
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
            'me', 'te', 'se', 'nos', 'les', 'lo', 'le',
            'mi', 'tu', 'su', 'mis', 'tus', 'sus',
            # Preposiciones y conjunciones
            'de', 'del', 'en', 'con', 'para', 'por', 'a', 'al',
            'y', 'o', 'que', 'como', 'cual', 'cuales',
            # Adjetivos/adverbios comunes
            'algunas', 'algunos', 'alguna', 'alguno', 'algo',
            'bueno', 'buena', 'buenos', 'buenas', 'mejor', 'mejores',
            'mas', 'muy', 'mucho', 'mucha', 'muchos', 'muchas',
            # Interrogativos
            'que', 'cual', 'cuales', 'como', 'donde', 'cuando', 'cuanto', 'cuanta',
            # Cortesía
            'favor', 'gracias', 'por', 'porfavor', 'porfa', 'hola', 'oye'
        }

        # Limpiar query: quitar signos de puntuación
        query_clean = re.sub(r'[¿?¡!.,;:()"\']', ' ', query.lower())

        # Separar en palabras
        words = query_clean.split()

        # Filtrar stop words y palabras muy cortas (<=2 caracteres)
        keywords = [w for w in words if w not in stop_words and len(w) > 2]

        # Si después de filtrar no queda nada, usar palabras originales >2 chars
        if not keywords:
            keywords = [w for w in words if len(w) > 2]

        # Si aún no hay nada, devolver query original
        if not keywords:
            return query

        # Unir palabras clave
        return ' '.join(keywords)

    @staticmethod
    def rerank_results(results: list, query: str) -> list:
        """
        Re-rankea resultados basado en relevancia adicional
        """
        query_lower = query.lower()

        for result in results:
            # Penalización si el score original es muy bajo
            if result['score'] < 0.4:
                result['score'] *= 0.8  # 20% penalización

            # Boost adicional para matches exactos en categoría
            if 'category' in result:
                if query_lower in result['category'].lower():
                    result['score'] = min(result['score'] * 1.15, 1.0)

        return results
