<?php
$output = "# MODELOS DEL SISTEMA\n\n";
$modelsDir = 'models';
$models = scandir($modelsDir);

$output .= "## 📊 TOTAL DE MODELOS: " . (count($models) - 2) . "\n\n";

foreach ($models as $model) {
    if (pathinfo($model, PATHINFO_EXTENSION) === 'php') {
        $output .= "## 🔹 " . basename($model, '.php') . "\n\n";
        
        // Leer contenido básico del modelo
        $content = file_get_contents($modelsDir . '/' . $model);
        
        // Extraer información básica
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $output .= "**Clase:** `{$matches[1]}`\n\n";
        }
        
        // Contar métodos públicos
        $publicMethods = preg_match_all('/public\s+function\s+(\w+)/', $content, $methodMatches);
        $output .= "**Métodos públicos:** $publicMethods\n\n";
        
        // Buscar relaciones comunes
        if (strpos($content, 'hasOne') !== false) $output .= "📌 **Contiene relaciones hasOne**\n";
        if (strpos($content, 'hasMany') !== false) $output .= "📌 **Contiene relaciones hasMany**\n";
        if (strpos($content, 'rules()') !== false) $output .= "📌 **Contiene reglas de validación**\n";
        
        $output .= "\n---\n\n";
    }
}

file_put_contents('docs/modelos_detallados.md', $output);
?>