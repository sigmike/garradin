<?php

namespace Garradin;

class Compta_Comptes_Bancaires extends Compta_Comptes
{
    const NUMERO_PARENT_COMPTES = 512;

    public function add($data)
    {
        $db = DB::getInstance();

        $data['parent'] = self::NUMERO_PARENT_COMPTES;
        $data['id'] = null;

        $this->_checkBankFields($data);

        $new_id = parent::add($data);

        $db = DB::getInstance();
        $db->simpleInsert('compta_comptes_bancaires', array(
            'id'        =>  $new_id,
            'banque'    =>  $data['banque'],
            'iban'      =>  $data['iban'],
            'bic'       =>  $data['bic'],
        ));

        return $new_id;
    }

    public function edit($id, $data)
    {
        $db = DB::getInstance();

        if (!$db->simpleQuerySingle('SELECT 1 FROM compta_comptes_bancaires WHERE id = ?;', false, $id))
        {
            throw new UserException('Ce compte n\'est pas un compte bancaire.');
        }

        $this->_checkBankFields($data);
        $result = parent::edit($id, $data);

        if (!$result)
        {
            return $result;
        }

        $db->simpleUpdate('compta_comptes_bancaires', array(
            'banque'    =>  $data['banque'],
            'iban'      =>  $data['iban'],
            'bic'       =>  $data['bic'],
        ), 'id = \''.$db->escapeString(trim($id)).'\'');

        return true;
    }

    public function delete($id)
    {
        $db = DB::getInstance();
        if (!$db->simpleQuerySingle('SELECT 1 FROM compta_comptes_bancaires WHERE id = ?;', false, trim($id)))
        {
            throw new UserException('Ce compte n\'est pas un compte bancaire.');
        }

        $db->simpleExec('DELETE FROM compta_comptes_bancaires WHERE id = ?;', trim($id));
        $return = parent::delete($id);

        return $return;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc
            ON c.id = cc.id
            WHERE c.id = ?;', true, $id);
    }

    public function getList($parent = false)
    {
        $db = DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT c.id AS id, * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc ON c.id = cc.id
            WHERE c.parent = '.self::NUMERO_PARENT_COMPTES.' ORDER BY c.id;');
    }

    protected function _checkBankFields(&$data)
    {
        if (empty($data['banque']) || !trim($data['banque']))
        {
            throw new UserException('Le nom de la banque ne peut rester vide.');
        }

        if (empty($data['bic']))
        {
            $data['bic'] = '';
        }
        else
        {
            $data['bic'] = trim(strtoupper($data['bic']));
            $data['bic'] = preg_replace('![^\dA-Z]!', '', $data['bic']);

            if (!utils::checkBIC($data['bic']))
            {
                throw new UserException('Code BIC/SWIFT invalide.');
            }
        }

        if (empty($data['iban']))
        {
            $data['iban'] = '';
        }
        else
        {
            $data['iban'] = trim(strtoupper($data['iban']));
            $data['iban'] = preg_replace('![^\dA-Z]!', '', $data['iban']);

            if (!utils::checkIBAN($data['iban']))
            {
                throw new UserException('Code IBAN invalide.');
            }
        }

        return true;
    }
}

?>