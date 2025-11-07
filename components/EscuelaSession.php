<?php
// components/EscuelaSession.php
namespace app\components;

use Yii;
use yii\base\Component;
use app\models\Escuela;

class EscuelaSession extends Component
{
    const SESSION_ID_ESCUELA = 'id_escuela';
    const SESSION_NOMBRE_ESCUELA = 'nombre_escuela';
    const SESSION_ESCUELA_DATA = 'escuela_data';

    /**
     * Establece la escuela en sesión con validación completa
     */
    public function setEscuela($id_escuela, $nombre_escuela)
    {
        // CORRECCIÓN: Validación adicional para prevenir datos inválidos
        if (!$id_escuela || $id_escuela === '0' || !$nombre_escuela || $nombre_escuela === '') {
            Yii::error("Intento de establecer escuela con datos inválidos: ID=$id_escuela, Nombre=$nombre_escuela");
            return false;
        }
        
        $id_escuela = (int)$id_escuela;
        $nombre_escuela = trim($nombre_escuela);
        
        // VERIFICAR EN BASE DE DATOS QUE LA ESCUELA EXISTE Y ESTÁ ACTIVA
        $escuela = Escuela::findOne([
            'id' => $id_escuela, 
            'eliminado' => false
        ]);
        
        if (!$escuela) {
            Yii::error("Escuela no encontrada o eliminada: ID=$id_escuela");
            return false;
        }
        
        // Validar que el nombre coincide con el ID (podemos ser flexibles)
        if (strtolower($escuela->nombre) !== strtolower($nombre_escuela)) {
            Yii::warning("Discrepancia en nombre de escuela: BD='{$escuela->nombre}', Recibido='$nombre_escuela'");
            // Usar el nombre correcto de la BD para consistencia
            $nombre_escuela = $escuela->nombre;
        }
        
        Yii::$app->session->set(self::SESSION_ID_ESCUELA, $id_escuela);
        Yii::$app->session->set(self::SESSION_NOMBRE_ESCUELA, $nombre_escuela);
        Yii::$app->session->set(self::SESSION_ESCUELA_DATA, [
            'id' => $id_escuela,
            'nombre' => $nombre_escuela,
            'direccion' => $escuela->direccion_administrativa,
            'telefono' => $escuela->telefono,
            'email' => $escuela->email,
            'timestamp' => time()
        ]);
        
        Yii::info("Escuela establecida correctamente: ID=$id_escuela, Nombre=$nombre_escuela");
        return true;
    }

    /**
     * Obtiene el ID de la escuela desde sesión con revalidación
     */
    public function getIdEscuela()
    {
        $id = Yii::$app->session->get(self::SESSION_ID_ESCUELA);
        
        // CORRECCIÓN: Validar que sea un ID válido
        if ($id === null || $id === '' || $id === '0' || $id <= 0) {
            return null;
        }
        
        // Revalidar periódicamente que la escuela aún existe (cada 30 minutos)
        $data = Yii::$app->session->get(self::SESSION_ESCUELA_DATA);
        if ($data && (time() - $data['timestamp'] > 1800)) { // 1800 segundos = 30 minutos
            $escuela = Escuela::findOne([
                'id' => $id, 
                'eliminado' => false
            ]);
            
            if (!$escuela) {
                Yii::warning("Escuela en sesión ya no existe: ID=$id - Limpiando sesión");
                $this->clearEscuela();
                return null;
            }
            
            // Actualizar timestamp
            $data['timestamp'] = time();
            Yii::$app->session->set(self::SESSION_ESCUELA_DATA, $data);
        }
        
        return (int)$id;
    }

    /**
     * Obtiene el nombre de la escuela desde sesión
     */
    public function getNombreEscuela()
    {
        return Yii::$app->session->get(self::SESSION_NOMBRE_ESCUELA);
    }

    /**
     * Verifica si hay una escuela establecida en sesión
     */
    public function hasEscuela()
    {
        return $this->getIdEscuela() !== null;
    }

    /**
     * Limpia la escuela de la sesión
     */
    public function clearEscuela()
    {
        Yii::$app->session->remove(self::SESSION_ID_ESCUELA);
        Yii::$app->session->remove(self::SESSION_NOMBRE_ESCUELA);
        Yii::$app->session->remove(self::SESSION_ESCUELA_DATA);
        Yii::info("Escuela limpiada de sesión");
        return true;
    }

    /**
     * Obtiene información completa de la escuela
     */
    public function getEscuela()
    {
        return [
            'id' => $this->getIdEscuela(),
            'nombre' => $this->getNombreEscuela()
        ];
    }

    /**
     * Obtiene datos completos y frescos de la escuela
     */
    public function getEscuelaData()
    {
        $data = Yii::$app->session->get(self::SESSION_ESCUELA_DATA);
        
        // Si no hay datos en sesión o están vencidos, cargar desde BD
        if (!$data || (time() - $data['timestamp'] > 3600)) {
            $id = $this->getIdEscuela();
            if ($id) {
                $escuela = Escuela::findOne($id);
                if ($escuela) {
                    $data = [
                        'id' => $escuela->id,
                        'nombre' => $escuela->nombre,
                        'direccion' => $escuela->direccion_administrativa,
                        'telefono' => $escuela->telefono,
                        'email' => $escuela->email,
                        'timestamp' => time()
                    ];
                    Yii::$app->session->set(self::SESSION_ESCUELA_DATA, $data);
                }
            }
        }
        
        return $data;
    }

    /**
     * Verifica forzadamente que la escuela en sesión aún existe
     */
    public function revalidarEscuela()
    {
        $id = $this->getIdEscuela();
        if (!$id) {
            return false;
        }
        
        $escuela = Escuela::findOne([
            'id' => $id,
            'eliminado' => false
        ]);
        
        if (!$escuela) {
            $this->clearEscuela();
            return false;
        }
        
        // Actualizar datos en sesión
        $data = [
            'id' => $escuela->id,
            'nombre' => $escuela->nombre,
            'direccion' => $escuela->direccion_administrativa,
            'telefono' => $escuela->telefono,
            'email' => $escuela->email,
            'timestamp' => time()
        ];
        Yii::$app->session->set(self::SESSION_ESCUELA_DATA, $data);
        
        return true;
    }
}