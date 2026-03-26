<?php

namespace EasyLocalAI\Core;

/**
 * EasyLocalAI - Service Container (Simple DI)
 * Centralise l'instanciation des services et la gestion des dépendances.
 */
class Container {
    private static $instances = [];
    private static $factories = [];

    /**
     * Enregistre un service via une factory (closure).
     */
    public static function register($name, callable $factory) {
        self::$factories[$name] = $factory;
    }

    /**
     * Récupère une instance unique d'un service (Singleton).
     */
    public static function get($name) {
        if (!isset(self::$instances[$name])) {
            if (!isset(self::$factories[$name])) {
                throw new \Exception("Le service '$name' n'est pas enregistré.");
            }
            self::$instances[$name] = self::$factories[$name]();
        }
        return self::$instances[$name];
    }

    /**
     * Permet d'injecter manuellement une instance (pour les tests).
     */
    public static function set($name, $instance) {
        self::$instances[$name] = $instance;
    }
}
