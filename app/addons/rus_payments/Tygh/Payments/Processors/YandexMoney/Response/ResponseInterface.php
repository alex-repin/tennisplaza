<?php

namespace Tygh\Payments\Processors\YandexMoney\Response;

/**
 * @author Zmicier Aliakseyeu <z.aliakseyeu@gmail.com>
 */
interface ResponseInterface
{
    /**
     * @return string
     */
    public function getError();

    /**
     * @return boolean
     */
    public function isSuccess();
}
