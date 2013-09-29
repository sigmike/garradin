{include file="admin/_head.tpl" title="Garradin - Installation"}

{if $disabled}
    <p class="error">Garradin est déjà installé.</p>
{else}
    <p class="intro">
        Bienvenue dans Garradin !
        Veuillez remplir les quelques informations suivantes pour terminer
        l'installation.
    </p>

    {if !empty($error)}
        <p class="error">{$error|escape}</p>
    {/if}

    <form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations sur l'association</legend>
        <dl>
            <dt><label for="f_nom_asso">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_asso" id="f_nom_asso" value="{form_field name=nom_asso}" /></dd>
            <dt><label for="f_email_asso">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_asso" id="f_email_asso" value="{form_field name=email_asso}" /></dd>
            <dt><label for="f_adresse_asso">Adresse postale</label></dt>
            <dd><textarea cols="50" rows="5" name="adresse_asso" id="f_adresse_asso">{form_field name=adresse_asso}</textarea></dd>
            <dt><label for="f_site_asso">Site web</label></dt>
            <dd><input type="url" name="site_asso" id="f_site_asso" value="{form_field name=site_asso}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Informations sur le premier membre</legend>
        <dl>
            <dt><label for="f_nom_membre">Nom et prénom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_membre" id="f_nom_membre" value="{form_field name=nom_membre}" /></dd>
            <dt><label for="f_cat_membre">Catégorie du membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="tip">Par exemple : bureau, conseil d'administration, présidente, trésorier, etc.</dd>
            <dd><input type="text" name="cat_membre" id="f_cat_membre" value="{form_field name=cat_membre}" /></dd>
            <dt><label for="f_email_membre">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_membre" id="f_email_membre" value="{form_field name=email_membre}" /></dd>
            <dt><label for="f_passe_membre">Mot de passe</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">
                Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
                et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
            </dd>
            <dd class="help">
                Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="password_suggest" value="{$passphrase|escape}" />
            </dd>
            <dd><input type="password" name="passe_membre" id="f_passe_membre" value="{form_field name=passe_membre}" pattern=".{ldelim}5,{rdelim}" /></dd>
            <dt><label for="f_repasse_membre">Encore le mot de passe</label> (vérification) <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="password" name="repasse_membre" id="f_repasse_membre" value="{form_field name=repasse_membre}" pattern=".{ldelim}5,{rdelim}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="install"}
        <input type="submit" name="save" value="Terminer l'installation &rarr;" />
    </p>

    <script type="text/javascript" src="{$admin_url}static/password.js"></script>
    <script type="text/javascript">
    initPasswordField('password_suggest', 'f_passe_membre', 'f_repasse_membre');
    </script>

    </form>
{/if}

{include file="admin/_foot.tpl"}