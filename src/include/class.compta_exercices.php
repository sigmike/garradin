<?php

namespace Garradin;

class Compta_Exercices
{
    public function add($data)
    {
        $this->_checkFields($data);

        $db = DB::getInstance();

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE
            (debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin);', false,
            array('debut' => $data['debut'], 'fin' => $data['fin'])))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        if ($db->querySingle('SELECT 1 FROM compta_exercices WHERE cloture = 0;'))
        {
            throw new UserException('Il n\'est pas possible de créer un nouvel exercice tant qu\'il existe un exercice non-clôturé.');
        }

        $db->simpleInsert('compta_exercices', array(
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ));

        return $db->lastInsertRowId();
    }

    public function edit($id, $data)
    {
        $db = DB::getInstance();

        $this->_checkFields($data);

        // Evitons que les exercices se croisent
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE id != :id AND
            ((debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin));', false,
            array('debut' => $data['debut'], 'fin' => $data['fin'], 'id' => (int) $id)))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        // On vérifie qu'on ne va pas mettre des opérations en dehors de tout exercice
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ?
            AND date < ? LIMIT 1;', false, (int)$id, $data['debut']))
        {
            throw new UserException('Des opérations de cet exercice ont une date antérieure à la date de début de l\'exercice.');
        }

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ?
            AND date > ? LIMIT 1;', false, (int)$id, $data['fin']))
        {
            throw new UserException('Des opérations de cet exercice ont une date postérieure à la date de fin de l\'exercice.');
        }

        $db->simpleUpdate('compta_exercices', array(
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ), 'id = \''.(int)$id.'\'');

        return true;
    }

    /**
     * Clôturer un exercice et en ouvrir un nouveau
     * Le report à nouveau n'est pas effectué automatiquement par cette fonction, voir doReports pour ça.
     * @param  integer  $id     ID de l'exercice à clôturer
     * @param  string   $end    Date de clôture de l'exercice au format Y-m-d
     * @return integer          L'ID du nouvel exercice créé
     */
    public function close($id, $end)
    {
        $db = DB::getInstance();

        if (!utils::checkDate($end))
        {
            throw new UserException('Date de fin vide ou invalide.');
        }

        $db->exec('BEGIN;');

        // Clôture de l'exercice
        $db->simpleUpdate('compta_exercices', array(
            'cloture'   =>  1,
            'fin'       =>  $end,
        ), 'id = \''.(int)$id.'\'');

        // Date de début du nouvel exercice : lendemain de la clôture du précédent exercice
        $new_begin = utils::modifyDate($end, '+1 day');

        // Date de fin du nouvel exercice : un an après l'ouverture
        $new_end = utils::modifyDate($new_begin, '+1 year');

        // Enfin sauf s'il existe déjà des opérations après cette date, auquel cas la date de fin
        // est fixée à la date de la dernière opération, ceci pour ne pas avoir d'opération
        // orpheline d'exercice
        $last = $db->simpleQuerySingle('SELECT date FROM compta_journal WHERE id_exercice = ? AND date >= ? ORDER BY date DESC LIMIT 1;', false, $id, $new_end);
        $new_end = $last ?: $new_end;

        // Création du nouvel exercice
        $new_id = $this->add(array(
            'debut'     =>  $new_begin,
            'fin'       =>  $new_end,
            'libelle'   =>  'Nouvel exercice'
            )
        );

        // Ré-attribution des opérations de l'exercice à clôturer qui ne sont pas dans son
        // intervale au nouvel exercice
        $db->simpleExec('UPDATE compta_journal SET id_exercice = ? WHERE id_exercice = ? AND date >= ?;',
            $new_id, $id, $new_begin);

        $db->exec('END;');

        return $new_id;
    }

    /**
     * Créer les reports à nouveau issus de l'exercice $old_id dans le nouvel exercice courant
     * @param  integer $old_id  ID de l'ancien exercice
     * @param  integer $new_id  ID du nouvel exercice
     * @param  string  $date    Date Y-m-d donnée aux opérations créées
     * @return boolean          true si succès
     */
    public function doReports($old_id, $date)
    {
        $db = DB::getInstance();

        $db->exec('BEGIN;');

        $this->solderResultat($old_id, $date);

        $report_crediteur = 110;
        $report_debiteur  = 119;

        // Récupérer chacun des comptes de bilan et leurs soldes (uniquement les classes 1 à 5)
        $statement = $db->simpleStatement('SELECT compta_comptes.id AS compte, compta_comptes.position AS position,
            COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_debit = compta_comptes.id AND id_exercice = :id), 0)
            - COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_credit = compta_comptes.id AND id_exercice = :id), 0) AS solde
            FROM compta_comptes 
            INNER JOIN compta_journal ON compta_comptes.id = compta_journal.compte_debit 
                OR compta_comptes.id = compta_journal.compte_credit
            WHERE id_exercice = :id AND solde != 0 AND CAST(substr(compta_comptes.id, 1, 1) AS INTEGER) <= 5
            GROUP BY compta_comptes.id;', array('id' => $old_id));

        $diff = 0;
        $journal = new Compta_Journal;

        while ($row = $statement->fetchArray(SQLITE3_ASSOC))
        {
            $solde = ($row['position'] & Compta_Comptes::ACTIF) ? abs($row['solde']) : -abs($row['solde']);
            $solde = round($solde, 2);

            $diff += $solde;

            if (empty($solde))
            {
                continue;
            }

            // Chaque solde de compte est reporté dans le nouvel exercice
            $journal->add(array(
                'libelle'       =>  'Report à nouveau',
                'date'          =>  $date,
                'montant'       =>  abs($solde),
                'compte_debit'  =>  ($solde < 0 ? NULL : $row['compte']),
                'compte_credit' =>  ($solde > 0 ? NULL : $row['compte']),
                'remarques'     =>  'Report de solde créé automatiquement à la clôture de l\'exercice précédent',
            ));
        }
        
        // FIXME utiliser $diff pour équilibrer

        $db->exec('END;');

        return true;
    }

    /**
     * Solder les comptes de charge et de produits de l'exercice N 
     * et les inscrire au résultat de l'exercice N+1
     * @param  integer  $exercice   ID de l'exercice à solder
     * @param  string   $date       Date de début de l'exercice Y-m-d
     * @return boolean              true en cas de succès
     */
    public function solderResultat($exercice, $date)
    {
        $db = DB::getInstance();

        $resultat_excedent = 120;
        $resultat_debiteur = 129;

        $resultat = $this->getCompteResultat($exercice);
        $resultat = $resultat['resultat'];

        if ($resultat != 0)
        {
            $journal = new Compta_Journal;
            $journal->add(array(
                'libelle'   =>  'Résultat de l\'exercice précédent',
                'date'      =>  $date,
                'montant'   =>  $resultat,
                'compte_debit'  =>  $resultat < 0 ? 129 : NULL,
                'compte_credit' =>  $resultat > 0 ? 120 : NULL,
            ));
        }

        return true;
    }
    
    public function delete($id)
    {
        $db = DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ? LIMIT 1;', false, $id))
        {
            throw new UserException('Cet exercice ne peut être supprimé car des opérations comptables y sont liées.');
        }

        $db->simpleExec('DELETE FROM compta_exercices WHERE id = ?;', (int)$id);

        return true;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin FROM compta_exercices WHERE id = ?;', true, (int)$id);
    }

    public function getCurrent()
    {
        $db = DB::getInstance();
        return $db->querySingle('SELECT *, strftime(\'%s\', debut) AS debut, strftime(\'%s\', fin) FROM compta_exercices
            WHERE cloture = 0 LIMIT 1;', true);
    }

    public function getCurrentId()
    {
        $db = DB::getInstance();
        return $db->querySingle('SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1;');
    }

    public function getList()
    {
        $db = DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT id, *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin,
            (SELECT COUNT(*) FROM compta_journal WHERE id_exercice = compta_exercices.id) AS nb_operations
            FROM compta_exercices ORDER BY fin DESC;', SQLITE3_ASSOC);
    }

    protected function _checkFields(&$data)
    {
        $db = DB::getInstance();

        if (empty($data['libelle']) || !trim($data['libelle']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        $data['libelle'] = trim($data['libelle']);

        if (empty($data['debut']) || !checkdate(substr($data['debut'], 5, 2), substr($data['debut'], 8, 2), substr($data['debut'], 0, 4)))
        {
            throw new UserException('Date de début vide ou invalide.');
        }

        if (empty($data['fin']) || !checkdate(substr($data['fin'], 5, 2), substr($data['fin'], 8, 2), substr($data['fin'], 0, 4)))
        {
            throw new UserException('Date de fin vide ou invalide.');
        }

        return true;
    }


    public function getJournal($exercice)
    {
        $db = DB::getInstance();
        $query = 'SELECT *, strftime(\'%s\', date) AS date FROM compta_journal
            WHERE id_exercice = '.(int)$exercice.' ORDER BY date, id;';
        return $db->simpleStatementFetch($query);
    }

    public function getGrandLivre($exercice)
    {
        $db = DB::getInstance();
        $livre = array('classes' => array(), 'debit' => 0.0, 'credit' => 0.0);

        $res = $db->prepare('SELECT compte FROM
            (SELECT compte_debit AS compte FROM compta_journal
                    WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte FROM compta_journal
                    WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_credit)
            ORDER BY base64(compte) COLLATE BINARY ASC;'
            )->execute();

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            $compte = $row[0];

            if (is_null($compte))
                continue;

            $classe = substr($compte, 0, 1);
            $parent = substr($compte, 0, 2);

            if (!array_key_exists($classe, $livre['classes']))
            {
                $livre['classes'][$classe] = array();
            }

            if (!array_key_exists($parent, $livre['classes'][$classe]))
            {
                $livre['classes'][$classe][$parent] = array(
                    'total'         =>  0.0,
                    'comptes'       =>  array(),
                );
            }

            $livre['classes'][$classe][$parent]['comptes'][$compte] = array('debit' => 0.0, 'credit' => 0.0, 'journal' => array());

            $livre['classes'][$classe][$parent]['comptes'][$compte]['journal'] = $db->simpleStatementFetch(
                'SELECT *, strftime(\'%s\', date) AS date FROM (
                    SELECT * FROM compta_journal WHERE compte_debit = :compte AND id_exercice = '.(int)$exercice.'
                    UNION
                    SELECT * FROM compta_journal WHERE compte_credit = :compte AND id_exercice = '.(int)$exercice.'
                    )
                ORDER BY date, numero_piece, id;', SQLITE3_ASSOC, array('compte' => $compte));

            $debit = (float) $db->simpleQuerySingle(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_debit = ? AND id_exercice = '.(int)$exercice.';',
                false, $compte);

            $credit = (float) $db->simpleQuerySingle(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_credit = ? AND id_exercice = '.(int)$exercice.';',
                false, $compte);

            $livre['classes'][$classe][$parent]['comptes'][$compte]['debit'] = $debit;
            $livre['classes'][$classe][$parent]['comptes'][$compte]['credit'] = $credit;

            $livre['classes'][$classe][$parent]['total'] += $debit;
            $livre['classes'][$classe][$parent]['total'] -= $credit;

            $livre['debit'] += $debit;
            $livre['credit'] += $credit;
        }

        $res->finalize();

        return $livre;
    }

    public function getCompteResultat($exercice)
    {
        $db = DB::getInstance();

        $charges    = array('comptes' => array(), 'total' => 0.0);
        $produits   = array('comptes' => array(), 'total' => 0.0);
        $resultat   = 0.0;

        $res = $db->prepare('SELECT compte, SUM(debit), SUM(credit)
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, 0 AS credit
                    FROM compta_journal WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, 0 AS debit, SUM(montant) AS credit
                    FROM compta_journal WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_credit)
            WHERE compte LIKE \'6%\' OR compte LIKE \'7%\'
            GROUP BY compte
            ORDER BY base64(compte) COLLATE BINARY ASC;'
            )->execute();

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            list($compte, $debit, $credit) = $row;
            $classe = substr($compte, 0, 1);
            $parent = substr($compte, 0, 2);

            if ($classe == 6)
            {
                if (!isset($charges['comptes'][$parent]))
                {
                    $charges['comptes'][$parent] = array('comptes' => array(), 'solde' => 0.0);
                }

                $solde = $debit - $credit;

                $charges['comptes'][$parent]['comptes'][$compte] = $solde;
                $charges['total'] += $solde;
                $charges['comptes'][$parent]['solde'] += $solde;
            }
            elseif ($classe == 7)
            {
                if (!isset($produits['comptes'][$parent]))
                {
                    $produits['comptes'][$parent] = array('comptes' => array(), 'solde' => 0.0);
                }

                $solde = $credit - $debit;

                $produits['comptes'][$parent]['comptes'][$compte] = $solde;
                $produits['total'] += $solde;
                $produits['comptes'][$parent]['solde'] += $solde;
            }
        }

        $res->finalize();

        $resultat = $produits['total'] - $charges['total'];

        return array('charges' => $charges, 'produits' => $produits, 'resultat' => $resultat);
    }

    /**
     * Calculer le bilan comptable pour l'exercice $exercice
     * @param  integer  $exercice   ID de l'exercice dont il faut produire le bilan
     * @param  boolean  $resultat   true s'il faut calculer le résultat de l'exercice (utile pour un exercice en cours)
     * @return array    Un tableau multi-dimensionnel avec deux clés : actif et passif
     */
    public function getBilan($exercice)
    {
        $db = DB::getInstance();

        $include = array(Compta_Comptes::ACTIF, Compta_Comptes::PASSIF,
            Compta_Comptes::PASSIF | Compta_Comptes::ACTIF);

        $actif      = array('comptes' => array(), 'total' => 0.0);
        $passif     = array('comptes' => array(), 'total' => 0.0);

        $resultat = $this->getCompteResultat($exercice);

        if ($resultat['resultat'] >= 0)
        {
            $passif['comptes']['12'] = array(
                'comptes'   =>  array('120' => $resultat['resultat']),
                'solde'     =>  $resultat['resultat']
            );

            $passif['total'] = $resultat['resultat'];
        }
        else
        {
            $passif['comptes']['12'] = array(
                'comptes'   =>  array('129' => $resultat['resultat']),
                'solde'     =>  $resultat['resultat']
            );

            $passif['total'] = $resultat['resultat'];
        }

        // Y'a sûrement moyen d'améliorer tout ça pour que le maximum de travail
        // soit fait au niveau du SQL, mais pour le moment ça marche
        $res = $db->prepare('SELECT compte, debit, credit, (SELECT position FROM compta_comptes WHERE id = compte) AS position
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, NULL AS credit
                    FROM compta_journal WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, NULL AS debit, SUM(montant) AS credit
                    FROM compta_journal WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_credit)
            WHERE compte IN (SELECT id FROM compta_comptes WHERE position IN ('.implode(', ', $include).'))
            ORDER BY base64(compte) COLLATE BINARY ASC;'
            )->execute();

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            list($compte, $debit, $credit, $position) = $row;
            $parent = substr($compte, 0, 2);
            $classe = $compte[0];

            if (($position & Compta_Comptes::ACTIF) && ($position & Compta_Comptes::PASSIF))
            {
                $solde = $debit - $credit;

                if ($solde > 0)
                    $position = 'actif';
                elseif ($solde < 0)
                    $position = 'passif';
                else
                    continue;

                $solde = abs($solde);
            }
            else if ($position & Compta_Comptes::ACTIF)
            {
                $position = 'actif';
                $solde = $debit - $credit;
            }
            else if ($position & Compta_Comptes::PASSIF)
            {
                $position = 'passif';
                $solde = $credit - $debit;
            }
            else
            {
                continue;
            }

            if (!isset(${$position}['comptes'][$parent]))
            {
                ${$position}['comptes'][$parent] = array('comptes' => array(), 'solde' => 0);
            }

            if (!isset(${$position}['comptes'][$parent]['comptes'][$compte]))
            {
                ${$position}['comptes'][$parent]['comptes'][$compte] = 0;
            }

            $solde = round($solde, 2);
            ${$position}['comptes'][$parent]['comptes'][$compte] += $solde;
            ${$position}['total'] += $solde;
            ${$position}['comptes'][$parent]['solde'] += $solde;
        }

        $res->finalize();

        // Suppression des soldes nuls
        foreach ($passif['comptes'] as $parent=>$p)
        {
            if ($p['solde'] == 0)
            {
                unset($passif['comptes'][$parent]);
                continue;
            }

            foreach ($p['comptes'] as $id=>$solde)
            {
                if ($solde == 0)
                {
                    unset($passif['comptes'][$parent]['comptes'][$id]);
                }
            }
        }

        foreach ($actif['comptes'] as $parent=>$p)
        {
            if (empty($p['solde']))
            {
                unset($actif['comptes'][$parent]);
                continue;
            }

            foreach ($p['comptes'] as $id=>$solde)
            {
                if (empty($solde))
                {
                    unset($actif['comptes'][$parent]['comptes'][$id]);
                }
            }
        }

        return array('actif' => $actif, 'passif' => $passif);
    }
}

?>