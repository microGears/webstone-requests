<?php
declare(strict_types=1);
/**
 * This file is part of WebStone\Requests.
 *
 * (C) 2009-2024 Maxim Kirichenko <kirichenko.maxim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebStone\Requests\Exceptions;

use Exception;
use WebStone\Requests\Constants;

class EHttpException extends Exception
{
    protected $status_code = 500;

    public function __construct($status, $message = '')
    {
        parent::__construct( $message, $this->status_code = $status );
    }

    public function getStatus()
    {
        return Constants::$httpStatuses[$this->status_code] ?? 'Unknown';
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }
}

/* End of file EHttp.php */
