<?php

use DirectoristAppToolkit\Helper\App_Settings;

if ( ! function_exists( 'directorist_app_toolkit_get_setting' ) ) {
    /**
     * Read a single app setting with new-option and legacy fallback support.
     *
     * @param string $field_key Setting key.
     * @param mixed  $default   Optional fallback when the field is unknown.
     *
     * @return mixed
     */
    function directorist_app_toolkit_get_setting( $field_key, $default = null ) {
        return App_Settings::get_setting( $field_key, $default );
    }
}

if ( ! function_exists( 'directorist_app_toolkit_get_tab_settings' ) ) {
    /**
     * Read all settings for a tab with new-option and legacy fallback support.
     *
     * @param string $tab_key Tab key.
     *
     * @return array
     */
    function directorist_app_toolkit_get_tab_settings( $tab_key ) {
        return App_Settings::get_tab_values( $tab_key );
    }
}


add_filter( 'directorist_rest_prepare_user', function( $response ) {
    // Support both WP_REST_Response object and associative array
    if ( is_object( $response ) && method_exists( $response, 'get_data' ) ) {
        $data = $response->get_data();
    } else {
        $data = is_array( $response ) ? $response : [];
    }

    $user_id = isset( $data['id'] ) && ! empty( $data['id'] ) ? $data['id'] : 0;

    // Add "is_admin": true|false by checking user capabilities
    $is_admin = false;
    if ( $user_id ) {
        $user = get_userdata( $user_id );
        if ( $user && is_a( $user, 'WP_User' ) ) {
            $is_admin = user_can( $user, 'manage_options' );
        }
    }

    $data['can_manage'] = $is_admin;

    // Set data back if WP_REST_Response object, else just return array
    if ( is_object( $response ) && method_exists( $response, 'set_data' ) ) {
        $response->set_data( $data );
        return $response;
    }

    return $data;
}, 10 );


add_filter( 'directorist_rest_prepare_user', function( $response ) {
    // Support both WP_REST_Response object and associative array
    if ( is_object( $response ) && method_exists( $response, 'get_data' ) ) {
        $data = $response->get_data();
    } else {
        $data = is_array( $response ) ? $response : [];
    }

    $user_type = isset( $data['user_type'] ) && ! empty( $data['user_type'] ) ? $data['user_type'] : '';
    $can_manage = isset( $data['can_manage'] ) && ! empty( $data['can_manage'] ) ? $data['can_manage'] : false;

    // Add "can_edit": true|false by checking user capabilities
    $can_edit = false;

    if( $can_manage || $user_type == 'author' )
    {
        $can_edit = true;
    }

    $data['can_edit'] = $can_edit;

    // Set data back if WP_REST_Response object, else just return array
    if ( is_object( $response ) && method_exists( $response, 'set_data' ) ) {
        $response->set_data( $data );
        return $response;
    }

    return $data;
}, 11 );

add_filter( 'directorist_rest_user_order_response_data', function( $response_data, $request ) {
    $status = $request->get_param( 'status' );
    if ( is_array( $response_data ) && isset( $response_data['data'] ) && is_array( $response_data['data'] ) ) {
        $filtered = array();
        foreach ( $response_data['data'] as $order ) {
            $order_status = is_object( $order ) ? ( $order->status ?? '' ) : ( $order['status'] ?? '' );
            if ( ! empty( $status ) && strtolower( trim( (string) $order_status ) ) !== strtolower( trim( (string) $status ) ) ) {
                continue;
            }

            $order_obj = is_object( $order ) ? $order : (object) $order;
            $plan = ( function_exists( 'directorist_get_pricing_plan_by_id' ) && ! empty( $order_obj->ref ) )
                ? directorist_get_pricing_plan_by_id( (int) $order_obj->ref )
                : null;

            $is_pay_per_listing = false;
            if ( $plan && ( ( $plan->type ?? '' ) === 'pay_per_listing' || ( class_exists( '\DirectoristPricingPlan\App\Enums\Plan\Type' ) && ( $plan->type ?? '' ) === \DirectoristPricingPlan\App\Enums\Plan\Type::PAY_PER_LISTING ) ) ) {
                $is_pay_per_listing = true;
            }

            if ( $is_pay_per_listing ) {
                $is_paid = in_array( strtolower( trim( (string) $order_status ) ), [ 'paid', 'completed', 'prepaid' ], true );
                $has_assigned_listing = ! empty( $order_obj->listing_id );

                if ( $is_paid && ! $has_assigned_listing ) {
                    $remaining_listings = 1;
                    $is_featured = ! empty( $order_obj->is_featured_listing ) || ! empty( $plan->is_featured );
                    $remaining_featured_listings = $is_featured ? 1 : 0;
                } else {
                    $remaining_listings = 0;
                    $remaining_featured_listings = 0;
                }
            } else {
                $remaining_listings = (int) apply_filters( 'directorist_rest_legacy_order_remaining_listings', 0, $order_obj );
                $remaining_featured_listings = (int) apply_filters( 'directorist_rest_legacy_order_remaining_featured_listings', 0, $order_obj );

                if ( 0 === $remaining_listings && $plan && function_exists( 'directorist_pricing_plans_singleton' ) ) {
                    $uses_repo = directorist_pricing_plans_singleton( \DirectoristPricingPlan\App\Repositories\UsesRepository::class );
                    if ( $uses_repo && method_exists( $uses_repo, 'get_listings_uses' ) ) {
                        $user_id = (int) ( $order_obj->user_id ?? get_current_user_id() );
                        $reg_uses = $uses_repo->get_listings_uses( $user_id, $plan, false );
                        $feat_uses = $uses_repo->get_listings_uses( $user_id, $plan, true );
                        $remaining_listings = (int) ( $reg_uses['remaining'] ?? 0 );
                        $remaining_featured_listings = (int) ( $feat_uses['remaining'] ?? 0 );
                    }
                }
            }

            if ( is_object( $order ) ) {
                $order->remaining_listings          = $remaining_listings;
                $order->remaining_featured_listings = $remaining_featured_listings;
            } else {
                $order['remaining_listings']          = $remaining_listings;
                $order['remaining_featured_listings'] = $remaining_featured_listings;
            }

            $filtered[] = $order;
        }
        $response_data['data'] = $filtered;
    }
    return $response_data;
}, 10, 2 );
add_filter( 'directorist_rest_response', function( $response, $context, $request ) {
    if ( ! is_wp_error( $response ) && $context === 'create_listing_item' && class_exists( '\DirectoristPricingPlan\App\Services\ListingPlanAssignmentService' ) ) {
        $listing_id = $response->data['id'] ?? 0;
        $plan_id = (int) $request->get_param( 'plan' );
        
        if ( $listing_id && $plan_id ) {
            try {
                $assignment_service = directorist_pricing_plans_singleton( \DirectoristPricingPlan\App\Services\ListingPlanAssignmentService::class );
                
                // assign() method takes $listing_id, $plan_id, $requesting_user_id
                $result = $assignment_service->assign( $listing_id, $plan_id, get_current_user_id() );
                
                if ( ! empty( $result['requires_payment'] ) ) {
                    $response->data['need_payment'] = true;
                    if ( ! empty( $result['redirect_url'] ) ) {
                        $response->data['redirect_url'] = $result['redirect_url'];
                    }
                }
            } catch ( \Exception $e ) {
                // If assignment fails, we can optionally return an error or just log it.
                // For now, add it to response for debugging.
                $response->data['plan_assignment_error'] = $e->getMessage();
            }
        }
    }
    return $response;
}, 10, 3 );
