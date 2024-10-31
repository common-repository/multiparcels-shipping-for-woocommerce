<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

/**
 * Class MP_Api_Client_Response
 */
class MP_Api_Client_Response
{
    /** @var bool Was the request successful? */
    protected $success = false;

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $validation_errors = [];

    /** @var array|null */
    protected $full_response = [];

    /** @var bool */
    protected $error = false;

    /** @var string */
    protected $error_message = '';

	/**
	 * MP_Api_Client_Response constructor.
	 *
	 * @param string $output
	 * @param int    $httpCode
	 * @param string $curl_error_message
	 */
    public function __construct($output, $httpCode, $curl_error_message = '')
    {
        $httpCodeGroup = floor($httpCode / 100);

        if ($decoded = json_decode($output, true)) {
            $this->full_response = $decoded;
        }

        if ($httpCode == 422) {
            $this->validation_errors = $this->full_response['errors'];
        } elseif ($httpCodeGroup == 2) {
            $this->success = true;

            if (array_key_exists('data', $this->full_response)) {
                $this->data = $this->full_response['data'];
            } else {
                $this->data = $this->full_response;
            }
        } elseif (array_key_exists('status', $this->full_response) && $this->full_response['status'] == 'error')  {
	        $this->error         = true;
	        $this->error_message = sprintf('%s (%s)', $this->full_response['message'], $httpCode);
        } elseif ( $curl_error_message ) {
	        $this->error         = true;
	        $this->error_message = $curl_error_message;
        }
    }

    /**
     * @return bool
     */
    public function was_successful()
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function has_validation_errors()
    {
        return (bool)count($this->validation_errors);
    }

    /**
     * @return array
     */
    public function get_validation_errors()
    {
        return $this->validation_errors;
    }

    /**
     * @return bool
     */
    public function has_error()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function get_error_message()
    {
        return $this->error_message;
    }

    /**
     * @return array
     */
    public function get_data()
    {
        return $this->data;
    }

	/**
	 * @return array|null
	 */
	public function get_full_response() {
		return $this->full_response;
	}
}
