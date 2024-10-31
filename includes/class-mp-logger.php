<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

/**
 * Class MP_Logger
 */
class MP_Logger
{
    const TYPE_SHIPMENT_CREATE = 'shipment_create';
    const TYPE_SHIPMENT_CONFIRM = 'shipment_confirm';
    const TYPE_SHIPMENT_DOWNLOAD_LABEL = 'shipment_download_label';

    /** @var bool */
    private $enabled = false;

    /** @var string */
    private $file_name = 'logger.log';

    /** @var resource */
    private $file_handle;

    public function __construct()
    {
        $this->enabled = MultiParcels()->options->getBool('logger_enabled');
        $this->prepare();
    }

    public function log($data, $event_type = null, $reference = null)
    {
        if ($this->enabled && $this->file_handle) {

            if ($event_type == null) {
                $event_type = 'undefined';
            }

            $writes   = [];
            $writes[] = sprintf("ET: %s \n", $event_type);
            $writes[] = sprintf("T: %s \n", date('Y-m-d H:i:s'));
            if ($reference) {
                $writes[] = sprintf("R: %s \n", $reference);
            }

            try {
                $writes[] = sprintf("C: %s \n", json_encode((array)$data));
            } catch (Exception $exception) {
                $writes[] = sprintf("C: %s - %s \n", 'EXCEPTION', $exception->getMessage());
            }

            $writes[] = str_repeat('-', 20) . "\n\n";

            foreach ($writes as $write) {
                fwrite($this->file_handle, $write);
            }

        }
    }

    private function file_name()
    {
        return plugin_dir_path(__DIR__) . $this->file_name;
    }

    private function prepare()
    {
        if ($this->enabled) {
            if ( ! file_exists($this->file_name())) {
                if ($file_handle = @fopen($this->file_name(), 'w')) {
                    fwrite($file_handle, '');
                    fclose($file_handle);

                    chmod($this->file_name(), 0600);
                }
            }
            // If the file still does not exists - disable the logger
            if ( ! file_exists($this->file_name())) {
                $this->enabled = false;
            }

            $this->file_handle = fopen($this->file_name(), 'a');
        }
    }

    public function clear()
    {
        @unlink($this->file_name());
    }
}

return new MP_Logger();