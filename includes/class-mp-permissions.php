<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

/**
 * Class MP_Permissions
 */
class MP_Permissions
{
    const NONE = 'none';
    const LIMITED = 'limited';
    const FULL = 'full';

    /** @var string|null */
    protected $permission;

    /**
     * MP_Permissions constructor.
     */
    public function __construct()
    {
        $this->permission = MultiParcels()->options->get('permissions', true);

        if ($this->permission == null) {
            $this->update();
            MultiParcels()->carriers->update();
            MultiParcels()->locations->update();
        }
    }

    public function get()
    {
        return $this->permission;
    }

    /**
     * @return bool
     */
    public function isFull()
    {
        return $this->permission == self::FULL;
    }

    /**
     * If the feature is only for limited - allow it for full too
     *
     * @return bool
     */
    public function isLimited()
    {
        return in_array($this->permission, [self::LIMITED, self::FULL]);
    }

    /**
     * Check to only make limited permissions pass
     *
     * @return bool
     */
    public function isLimitedStrictly()
    {
        return $this->permission == self::LIMITED;
    }

    public function update()
    {
        $response = MultiParcels()->api_client->request('restricted_api/permissions', 'POST', [
            'api_key'    => MultiParcels()->options->get_api_key(),
            'domain'     => get_bloginfo('wpurl'),
            'version'    => MultiParcels()->version,
            'wc_version' => WC_VERSION,
        ]);

        if ($response->was_successful()) {
            $data           = $response->get_data();
            $permissions    = $data['permission'];
            $features       = $data['features'];
            $shipment_limit = $data['shipment_limit'];

            MultiParcels()->options->set('permissions', $permissions, true);
            MultiParcels()->options->set('features', $features, true);
            MultiParcels()->options->set('shipment_limit', $shipment_limit, true);
            $this->permission = $permissions;
        }
    }

    /**
     * @param  mixed  $permissions
     */
    public function set($permissions)
    {
        MultiParcels()->options->set('permissions', $permissions, true);
    }

    /**
     * @return bool
     */
    public function is_none()
    {
        return $this->permission == self::NONE;
    }

    /**
     * @return bool
     */
    public function hasAddressAutoComplete()
    {
        $features = MultiParcels()->options->get('features', true);

        if ( ! $this->isFull()) {
            return false;
        }

        if (is_array($features) && array_search('address_autocomplete', $features) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function addressAutoCompleteEnabled()
    {
        $settings = MultiParcels()->options->get('address_autocomplete', true);

        if ( ! $this->isFull()) {
            return false;
        }

        if (is_array($settings) && array_key_exists('enabled', $settings) && $settings['enabled'] == 1) {
            return true;
        }

        return false;
    }
}

return new MP_Permissions();
