<?php
    // this file is mainly for cleaning and ensuring route paths are well done
    
    /**
     * Normalize a path like "/admin/setup/" -> "admin/setup"
     */
    function clean_route_name($path)
    {
        return trim($path, "/");
    }

    /**
     * Register a route name into the map
     * Priority:
     * 1. Explicit "name" in route definition
     * 2. Auto-generated from path (actual path)
     */
    function register_route_name($path, $config, &$namedRoutes)
    {
        // explicit name always wins
        if (!empty($config['name'])) {
            $namedRoutes[$config['name']] = $path;
            return;
        }

        // auto-generate name based on actual URL path
        $autoName = clean_route_name($path);

        // ensure non-empty name
        if ($autoName === "") {
            $autoName = "home";
        }

        $namedRoutes[$autoName] = $path;
    }

    /**
     * scan routes.php structure recursively
     */
    function build_named_routes($routes, $prefix = "")
    {
        global $namedRoutes;

        foreach ($routes as $path => $config) {

            // simple route
            if (!isset($config['prefix'])) {

                register_route_name($path, $config, $namedRoutes);
            }

            // grouped routes
            if (isset($config['prefix']) && isset($config['routes'])) {

                $groupPrefix = $config['prefix'];

                foreach ($config['routes'] as $subPath => $subConfig) {

                    $fullPath = $groupPrefix . $subPath;

                    register_route_name($fullPath, $subConfig, $namedRoutes);
                }
            }
        }
    }

    // build all route names
    build_named_routes($routes);
