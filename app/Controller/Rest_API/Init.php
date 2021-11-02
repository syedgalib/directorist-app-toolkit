<?php
/**
 * Initialize Rest API
 *
 * @package DirectoristAppToolkit\Controller\Rest_API
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API;

defined( 'ABSPATH' ) || exit;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;


class Init {

    use HelperTraits\Service_Registrar;

    public function __construct() {
        $this->register_serivces( $this->get_controllers() );
    }

    /**
     * Get the controllers
     * 
     * @return array a list of controllers
     * 
     */
    public static function get_controllers() {
        return [
            Version_1\Init::class,
        ];
    }
}
