<?php
namespace app\controllers;

use Yii;
use app\components\CustomRoute;
use yii\web\Controller;

/**
 * Controlador personalizado para la gestión de rutas de mdm/admin.
 * Utiliza CustomRoute para detectar rutas y filtra correctamente las asignadas.
 */
class RouteController extends \mdm\admin\controllers\RouteController
{
    /**
     * Muestra las rutas disponibles (no asignadas) y las asignadas.
     */
    public function actionIndex()
    {
        // 1. Obtener todas las rutas detectadas por CustomRoute
        $allRoutes = CustomRoute::getAppRoutes();

        // 2. Obtener las rutas ya asignadas (permisos que empiezan con '/')
        $auth = Yii::$app->authManager;
        $assignedRoutes = [];
        foreach ($auth->getPermissions() as $name => $permission) {
            if (strpos($name, '/') === 0) {
                $assignedRoutes[] = $name;
            }
        }

        // 3. Rutas disponibles = totales - asignadas
        $availableRoutes = array_diff($allRoutes, $assignedRoutes);

        // 4. Preparar el array en el formato que espera la vista (reindexar)
        $routes = [
            'available' => array_values($availableRoutes),
            'assigned'  => array_values($assignedRoutes),
        ];

        return $this->render('index', [
            'routes' => $routes,
        ]);
    }

    /**
     * Refresca la lista de rutas redirigiendo al index.
     */
    public function actionRefresh()
    {
        return $this->redirect(['index']);
    }

    // Las acciones 'assign', 'remove' y 'create' se heredan del padre.
    // Si necesitas personalizarlas (por ejemplo, para agregar CSRF), puedes
    // sobrescribirlas aquí llamando al padre.
}