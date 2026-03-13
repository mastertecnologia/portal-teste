<?php

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class Empresa extends Entity {
      protected $_accessible = [
            '*' => true,
            'id' => false
      ];

      protected function removeCaracteres($string) {
            $chars = array("?", "+", "-", ",", "(" , ")", "*", "&", ";", "=", "/", ".", ":", " ");

            $string = str_replace($chars, "", $string);

            return $string;
      }

      // Função para limpar o cnpj
      protected function _setCnpj($cnpj){
            if (strlen($cnpj) > 0) {
                  return $this->removeCaracteres($cnpj);
            }
      }

      // Função para limpar o fone
      protected function _setFone($fone){
            if (strlen($fone) > 0) {
                  return $this->removeCaracteres($fone);
            }
      }

      // Função para limpar o fone2
      protected function _setFone2($fone2){
            if (strlen($fone2) > 0) {
                  return $this->removeCaracteres($fone2);
            }
      }

      // Função para limpar o cep
      protected function _setCep($cep){
            if (strlen($cep) > 0) {
                  return $this->removeCaracteres($cep);
            }
      }

      // // Função para criptografar a senha
      // protected function _setSenhaadministrativa($password){
      //       if (strlen($password) > 0) {
      //             return (new DefaultPasswordHasher)->hash($password);
      //       }
      // }

      // // Função para criptografar a senha
      // protected function _setConfirmPassword($password){
      //       if (strlen($password) > 0) {
      //             return (new DefaultPasswordHasher)->hash($password);
      //       }
      // }
}
