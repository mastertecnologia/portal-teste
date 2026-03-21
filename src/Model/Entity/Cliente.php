<?php

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class Cliente extends Entity {
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

      // Função para limpar o cpf
      protected function _setCpf($cpf){
            if (strlen($cpf) > 0) {
                  return $this->removeCaracteres($cpf);
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

      protected function _setRg($rg) {
            if (strlen((string)$rg) > 0) {
                  return $this->removeCaracteres($rg);
            }
      }
}
