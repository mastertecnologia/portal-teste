<?php

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity {
      protected $_accessible = [
            '*' => true,
            'confirm_password' => true,
            'id' => false
      ];

      protected function removeCaracteres($string) {
            $chars = array("?", "+", "-", ",", "(" , ")", "*", "&", ";", "=", "/", ".", ":", " ");
            $string = str_replace($chars, "", $string);
            return $string;
      }

      // Função para criptografar a senha
      protected function _setPassword($password){
            if (strlen($password) > 0) {
                  return (new DefaultPasswordHasher)->hash($password);
            }
      }

      // Função para criptografar a senha
      protected function _setConfirmPassword($password){
            if (strlen($password) > 0) {
                  return (new DefaultPasswordHasher)->hash($password);
            }
      }

      // Função para limpar o cpf
      protected function _setCpf($cpf){
            if (strlen($cpf) > 0) {
                  return $this->removeCaracteres($cpf);
            }
      }
}
