<?php

namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientService
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

        $errors = $this->clientHasErrors($client);

        if($errors !== false){
            return [
                'success' => false,
                'cod_error' => '02',
                'message_error' => $errors,
            ];
        }

            $this->entityManager->persist($client);
            $this->entityManager->flush();

            if($client->getId()) {
                $response = [
                    'success' => true,
                    'cod_error' => '00',
                    'message_error' => 'Registro exitoso',
                ];
            }else{
                $response = [
                    'success' => false,
                    'cod_error' => $this->entityManager->getConnection()->errorCode(),
                    'message_error' => $this->entityManager->getConnection()->errorInfo(),
                ];
            }

        return $response;
    }

    private function clientHasErrors($client){
        $errors = $this->validator->validate($client);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage().', ';
            }
            return $messages;
        }
        return false;
    }
}