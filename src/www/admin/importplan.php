<?php
namespace Garradin;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../../include/init.php';

$config = Config::getInstance();

// Mise à jour plan comptable
$comptes = new Compta_Comptes;
$comptes->importPlan();

utils::clearCaches();

echo '<h2>Plan comptable import&eacute;.</h2>
<p><a href="'.WWW_URL.'admin/">Retour</a></p>';

echo '
</body>';

?>