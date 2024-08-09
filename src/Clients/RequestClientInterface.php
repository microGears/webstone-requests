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

namespace WebStone\Requests\Clients;
/**
 * RequestClientInterface
 *
 * @author Maxim Kirichenko <kirichenko.maxim@gmail.com>
 * @datetime 07.08.2024 11:32:00
 */
interface RequestClientInterface
{
    public function request(string $url, string $method = 'GET', $headers = [], $content = null): Response;
    public function isSupported():bool;
}
/** End of RequestClientInterface **/