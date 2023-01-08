<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Order;
use App\Entity\Wallet;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EpaycoService implements SoapInterface
{
    private $entityManager;
    private $validator;
    private $mailer;

    public function __construct(ManagerRegistry $doctrine, ValidatorInterface $validator, MailerInterface $mailer)
    {
        $this->entityManager = $doctrine->getManager();
        $this->validator = $validator;
        $this->mailer = $mailer;
    }

    public function registro_cliente($data)
    {
        $client = new Client();
        $client->setDocumento($data->Documento);
        $client->setNombres($data->Nombres);
        $client->setEmail($data->Email);
        $client->setCelular($data->Celular);

        $wallet = new Wallet();
        $wallet->setBalance(0);
        $client->setWallet($wallet);

        $errors = $this->validate($client);

        if ($errors !== false) {
            return [
                'success' => false,
                'cod_error' => self::ERROR_PROPERTY,
                'message_error' => $errors,
            ];
        }

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        if ($client->getId()) {
            return $this->createResponse(true, self::SUCCESS, 'Registro exitoso');
        }

        return $this->createResponse(false, $this->entityManager->getConnection()->errorCode(), $this->entityManager->getConnection()->errorInfo());

    }

    public function recarga_billetera($data)
    {
        if (is_array($response = $this->validateRecargaBilletera($data))) {
            return $response;
        }

        $client = $this->entityManager->getRepository(Client::class)->findOneBy(
            [
                'Documento' => $data->Documento,
                'Celular' => $data->Celular,
            ]);

        if (!$client) {
            return $this->createResponse(false, self::ERROR_NOT_FOUND, 'El cliente no existe');
        }

        if (!property_exists($data, 'Valor')) {
            return $this->createResponse(false, self::ERROR_PROPERTY, 'El valor de la recarga no puede ser 0');
        }

        $wallet = $client->getWallet();
        $wallet->setBalance($wallet->getBalance() + $data->Valor);

        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        return $this->createResponse(true, self::SUCCESS, 'Recarga Exitosa');

    }

    public function consultar_saldo($data)
    {
        $client = $this->entityManager->getRepository(Client::class)->findOneBy(
            [
                'Documento' => $data->Documento,
                'Celular' => $data->Celular,
            ]);

        if (!$client) {
            return $this->createResponse(false, self::ERROR_NOT_FOUND, 'El cliente no existe');
        }

        return $this->createResponse(true, self::SUCCESS, $client->getWallet()->getBalance());
    }

    public function pagar($data)
    {
        $client = $this->entityManager->getRepository(Client::class)->findOneBy(
            [
                'Documento' => $data->Documento,
                'Celular' => $data->Celular,
            ]);

        if (!$client) {
            return $this->createResponse(false, self::ERROR_NOT_FOUND, 'El cliente no existe');
        }

        if ($client->getWallet()->getBalance() < $data->Valor) {
            return $this->createResponse(false, self::ERROR_NOT_ENOUGH_BALANCE, 'Saldo insuficiente');
        }

        if ($data->Orden === '') {
            return $this->createResponse(false, self::ERROR_PROPERTY, 'La orden es requerida');
        }

        $order = $this->entityManager->getRepository(Order::class)->findOneBy(
            [
                'number' => $data->Orden,
                'client' => $client,
            ]);

        if ($order) {

            if ($order->getStatus() == Order::STATUS_PAID) {
                return $this->createResponse(false, self::ERROR_ORDER_ALLREADY_PAID, 'La orden ya fue pagada');
            }

            //$order->sendToken($this->mailer);
            return $this->createResponse(true, self::SUCCESS, $order->getSessionId());

        }

        $order = new Order();
        $order->setClient($client);
        $order->setNumber($data->Orden);
        $order->setAmount($data->Valor);
        $order->generateToken();
        $order->generateSession();
        $order->setStatus(Order::STATUS_PENDING);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        if ($order->getId()) {
            $order->sendToken($this->mailer);
            return $this->createResponse(true, self::SUCCESS, ['Se ha enviado un correo con el token de su compra ', 'Este es el numero de sesion de su compra ' . $order->getSessionId()]);
        }

        return $this->createResponse(false, $this->entityManager->getConnection()->errorCode(), $this->entityManager->getConnection()->errorInfo());

    }

    public function confirmar_pago($data)
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(
            [
                'session_id' => $data->Sesion,
                'token' => $data->Token,
            ]);

        if (!$order) {
            return $this->createResponse(false, self::ERROR_NOT_FOUND, 'La orden no existe');
        }

        if ($order->getStatus() == Order::STATUS_PAID) {
            return $this->createResponse(false, self::ERROR_ORDER_ALLREADY_PAID, 'La orden ya fue pagada');
        }
        $client = $order->getClient();
        $wallet = $client->getWallet();
        $wallet->setBalance($wallet->getBalance() - $order->getAmount());
        $order->setStatus(Order::STATUS_PAID);
        $this->entityManager->persist($order);
        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        return $this->createResponse(true, self::SUCCESS, 'Pago exitoso');
    }

    private function validateRecargaBilletera($data)
    {
        if (!is_numeric($data->Valor)) {
            return $this->createResponse(false, self::ERROR_PROPERTY, 'El valor de la recarga debe ser un valor numerico');
        }

        $data->Valor = doubleval($data->Valor);


        if ($data->Valor <= 0) {
            return $this->createResponse(false, self::ERROR_PROPERTY, 'El valor de la recarga debe ser mayor a 0');
        }
        return true;
    }

    public function validate($entity)
    {
        $errors = $this->validator->validate($entity);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage() . ', ';
            }
            return $messages;
        }
        return false;
    }

    public function createResponse($success, $cod_error, $message_error)
    {
        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
        ];
    }
}