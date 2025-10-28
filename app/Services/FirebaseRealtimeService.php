<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

class FirebaseRealtimeService
{
    protected Database $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database_url'));

        $this->database = $factory->createDatabase();
    }

    /**
     * Obtener instancia de la base de datos
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Obtener referencia a un path específico
     */
    public function getReference(string $path)
    {
        return $this->database->getReference($path);
    }

    /**
     * Escribir datos en un path
     */
    public function set(string $path, $data): void
    {
        $this->database->getReference($path)->set($data);
    }

    /**
     * Actualizar datos en un path
     */
    public function update(string $path, array $data): void
    {
        $this->database->getReference($path)->update($data);
    }

    /**
     * Leer datos de un path
     */
    public function get(string $path)
    {
        return $this->database->getReference($path)->getValue();
    }

    /**
     * Eliminar datos de un path
     */
    public function remove(string $path): void
    {
        $this->database->getReference($path)->remove();
    }

    /**
     * Push: crear un nuevo nodo con ID único
     */
    public function push(string $path, $data)
    {
        return $this->database->getReference($path)->push($data);
    }
}
