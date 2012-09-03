{include file="admin/_head.tpl" title="Comptes bancaires" current="compta/banques"}

<ul class="actions">
    <li class="current"><a href="{$www_url}admin/compta/banques.php">Comptes bancaires</a></li>
    <li><a href="{$www_url}admin/compta/compte_journal.php?id={Garradin_Compta_Comptes::CAISSE}">Journal de caisse</a></li>
    {if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}<li><strong><a href="{$www_url}admin/compta/banque_ajouter.php">Ajouter un compte bancaire</a></strong></li>{/if}
</ul>

    {if !empty($liste)}
        <dl class="catList">
        {foreach from=$liste item="compte"}
            <dt>{$compte.libelle|escape}</dt>
            <dd class="desc">
                IBAN : {$compte.iban|escape|format_iban}<br />
                BIC : {$compte.bic|escape}<br />
                RIB : {$compte.iban|escape|format_rib}
            </dd>
            <dd class="desc">Solde : {$compte.solde|escape} {$config.monnaie|escape}</dd>
            <dd class="actions">
                <a href="{$www_url}admin/compta/compte_journal.php?id={$compte.id|escape}">Journal</a>
            {if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}
                | <a href="{$www_url}admin/compta/banque_modifier.php?id={$compte.id|escape}">Modifier</a>
                | <a href="{$www_url}admin/compta/banque_supprimer.php?id={$compte.id|escape}">Supprimer</a>
            {/if}
            </dd>
        {/foreach}
        </dl>
    {else}
        <p class="alert">
            Aucun compte bancaire trouvé.
        </p>
    {/if}

{include file="admin/_foot.tpl"}