# python-embedding-service/models.py
"""
Carga del modelo de embeddings
"""
from sentence_transformers import SentenceTransformer
import os

print("📦 Cargando modelo de embeddings...")

# Cargar modelo (se descarga automáticamente la primera vez ~90MB)
model_name = os.getenv("EMBEDDING_MODEL", "sentence-transformers/all-MiniLM-L6-v2")
embedding_model = SentenceTransformer(model_name)

print(f"✅ Modelo {model_name} cargado correctamente")
print(f"   Dimensiones: {embedding_model.get_sentence_embedding_dimension()}")
