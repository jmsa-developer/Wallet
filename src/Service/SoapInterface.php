<?php

namespace App\Service;

use App\Repository\ClientRepository;
use Doctrine\ORM\EntityRepository;
use stdClass;

interface SoapInterface
{
    const SUCCESS = '00';
    const ERROR_PROPERTY = '01';
    const ERROR_NOT_FOUND = '02';
    const ERROR_NOT_ENOUGH_BALANCE = '03';
    const ERROR_ORDER_ALLREADY_PAID = '04';



    function createResponse($success, $cod_error, $message_error);
    function validate($entity);
}