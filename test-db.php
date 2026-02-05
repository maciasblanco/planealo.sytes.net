<?php
// test-db.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/web.php';

use yii\db\Query;
use yii\helpers\ArrayHelper;

echo "=== PRUEBA DE CONEXIÓN Y DATOS ===\n\n";

try {
    // 1. Verificar conexión
    $db = Yii::$app->db;
    if ($db->isActive) {
        echo "1. Conexión a BD: ✅ ACTIVA\n";
    } else {
        echo "1. Conexión a BD: ❌ INACTIVA\n";
    }
    
    // 2. Contar registros
    $count = (new Query())
        ->from('seguridad.menu')
        ->count('*', $db);
    echo "2. Total registros en seguridad.menu: $count\n";
    
    // 3. Consultar datos - CORREGIDO
    $query = new Query();
    $items = $query->select([
            'id', 
            'name', 
            'route', 
            'parent', 
            '"order" as menu_order'
        ])
        ->from('seguridad.menu')
        ->where(['parent' => null])
        ->orderBy('COALESCE("order", 99999) ASC')  // ← SIN comillas en 99999
        ->all($db);
    
    echo "3. Elementos raíz encontrados: " . count($items) . "\n";
    
    // 4. Mostrar algunos datos
    if (!empty($items)) {
        echo "\n=== PRIMEROS 5 REGISTROS ===\n";
        foreach (array_slice($items, 0, 5) as $item) {
            echo "- ID: {$item['id']}, Nombre: {$item['name']}, Ruta: {$item['route']}\n";
        }
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
}