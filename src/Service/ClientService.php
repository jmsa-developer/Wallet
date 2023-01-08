<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Wallet;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use stdClass;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientService implements SoapInterface
{
    private $entityManager;
    private $validator;

    public function __construct(ManagerRegistry $doctrine, ValidatorInterface $validator)
    {
        $this->entityManager = $doctrine->getManager();
        $this->validator = $validator;

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

        $wallet = $client->getWallet();
        $wallet->setBalance($wallet->getBalance() + $data->Valor);

        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        return $this->createResponse(true, self::SUCCESS, 'Recarga Exitosa');

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