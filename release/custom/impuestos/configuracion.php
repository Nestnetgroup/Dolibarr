<?php
require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formadmin.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/accounting.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formaccounting.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/impuestos.class.php";

//Cargar archivos de traducción requeridos por la página.
$langs->loadLangs([
    "errors",
    "admin",
    "main",
    "companies",
    "resource",
    "holiday",
    "accountancy",
    "hrm",
    "orders",
    "contracts",
    "projects",
    "propal",
    "bills",
    "interventions",
    "ticket",
]);

$action = GETPOST("action", "alpha") ? GETPOST("action", "alpha") : "view";
$rowid = GETPOST("rowid", "alpha");
$confirm = GETPOST("confirm", "alpha");
$code = GETPOST("code", "alpha");

$acts = [];
$actl = [];
$acts[0] = "activate";
$acts[1] = "disable";

$actl[0] = img_picto(
    $langs->trans("Disabled"),
    "switch_off",
    'class="size15x"'
);
$actl[1] = img_picto(
    $langs->trans("Activated"),
    "switch_on",
    'class="size15x"'
);

$actlpor[0] = img_picto(
    $langs->trans("Disabledpor"),
    "switch_off",
    'class="size15x"'
);
$actlpor[1] = img_picto(
    $langs->trans("Activatedpor"),
    "switch_on",
    'class="size15x"'
);

$actspor[0] = "activatepor";
$actspor[1] = "disablepor";

$scrollY = GETPOST("scrollY", "alpha");

// Cargar variable para paginación
$listoffset = GETPOST("listoffset");
$listlimit = GETPOST("listlimit") > 0 ? GETPOST("listlimit") : 1000; // To avoid too long dictionaries
$sortfield = GETPOST("sortfield", "aZ09comma");
$sortorder = GETPOST("sortorder", "aZ09comma");
$page = GETPOSTISSET("pageplusone")
    ? GETPOST("pageplusone") - 1
    : GETPOST("page", "int");
if (
    empty($page) ||
    $page < 0 ||
    GETPOST("button_search", "alpha") ||
    GETPOST("button_removefilter", "alpha")
) {
    //Si $page no está definido, o '' o -1 o si hacemos clic en borrar filtros
    $page = 0;
}
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_country_id = GETPOST("search_country_id", "int");
$search_code = GETPOST("search_code", "alpha");
$search_active = GETPOST("search_active", "alpha");

//$scrollX=GETPOST('search_active', 'alpha');

if (
    GETPOST("button_removefilter", "alpha") ||
    GETPOST("button_removefilter.x", "alpha") ||
    GETPOST("button_removefilter_x", "alpha")
) {
    $search_country_id = "";
    $search_code = "";
    $search_active = "";
}

if (GETPOST("saveporsentaje")) {
    $idimpuesto = GETPOST("idimpuesto");
    $valorporcentaje = GETPOST("porcentage");

    $tablename = "llx_porcentaje_impuestos";

    $sql = "SELECT count(rowid) as numrows FROM llx_porcentaje_impuestos WHERE fk_chargesociales=" .$idimpuesto ." AND porcentaje=" .$valorporcentaje;

    $result = $db->query($sql);
    if ($result) {
        $obj = $db->fetch_object($result);
        $numrows = intval($obj->numrows);
        if ($numrows > 0) {
            setEventMessages(
                "Ya existe el porcentaje del " .
                $valorporcentaje .
                " % para el impuesto",
                null,
                "errors"
            );
        } else {
            $sql =
                "INSERT INTO llx_porcentaje_impuestos(fk_chargesociales,porcentaje,fk_user_author,date_creation, active) VALUES (" .
                $idimpuesto .
                "," .
                $valorporcentaje .
                "," .
                $user->id .
                ",NOW(), 1)";
            $result = $db->query($sql);
            if (!$result) {
                dol_print_error($db);
            }
        }
    } else {
        dol_print_error($db);
    }

    $action = "expande";
    $rowid = $idimpuesto;
}

if (GETPOST("editporcentaje")) {
    $idimpuesto = GETPOST("idimpuesto");
    $valorporcentaje = GETPOST("valueedirpor");

    $sql =
        "UPDATE llx_porcentaje_impuestos SET porcentaje=" .
        $valorporcentaje .
        ", fk_user_modif=" .
        $user->id .
        " WHERE rowid=" .
        $idimpuesto;

    $result = $db->query($sql);
    if (!$result) {
        dol_print_error($db);
    }

    $action = "expande";
    $rowid = $idimpuesto;
}

if ( (GETPOST("actionadd") || GETPOST("actionmodify")) && !GETPOST("saveporsentaje")) {

    $listfield = explode( ",",str_replace(" ", "", "code,libelle,country,accountancy_code"));
    $listfieldinsert = explode(",","code,libelle,fk_pays,accountancy_code,base_importe,fk_aplicacion_impuestos");
    $listfieldmodify = explode(",","code,libelle,fk_pays,accountancy_code,base_importe,fk_aplicacion_impuestos");
    $listfieldvalue = explode(",","code,libelle,country,accountancy_code,base_importe,fk_aplicacion_impuestos");

    // Check that all mandatory fields are filled
    $ok = 1;

    if (GETPOSTISSET("code")) {
        if (GETPOST("code") == "0") {
            $ok = 0;
            setEventMessages(
                $langs->transnoentities("ErrorCodeCantContainZero"),
                null,
                "errors"
            );
        }
    }
    if (GETPOSTISSET("country") && GETPOST("country") == "0" && $id != 2) {
        if (
            in_array("id", ["DictionaryCompanyType", "DictionaryHolidayTypes"])
        ) {
            // Field country is no mandatory for such dictionaries
            $_POST["country"] = "";
        } else {
            $ok = 0;
            setEventMessages(
                $langs->transnoentities(
                    "ErrorFieldRequired",
                    $langs->transnoentities("Country")
                ),
                null,
                "errors"
            );
        }
    }

    // Clean some parameters

    if (GETPOST("accountancy_code") <= 0) {
        $_POST["accountancy_code"] = ""; // If empty, we force to null
    }

    $tablename = "c_chargesociales";
    $tablename = preg_replace(
        "/^" . preg_quote(MAIN_DB_PREFIX, "/") . "/",
        "",
        $tablename
    );

    // If check ok and action add, add the line
    if ($ok && GETPOST("actionadd")) {
        $newid = 0;
        if (!in_array("id", $listfieldinsert)) {
            // Get free id for insert
            $sql ="SELECT MAX(id) as newid FROM " . MAIN_DB_PREFIX . $tablename;
            $result = $db->query($sql);
            if ($result) {
                $obj = $db->fetch_object($result);
                $newid = ((int) $obj->newid) + 1;
            } else {
                dol_print_error($db);
            }
        }

        // Add new entry
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $tablename . " (";
        // List of fields
        if (!in_array("id", $listfieldinsert)) {
            $sql .= "id,";
        }

      
        $sql .= "code,libelle,fk_pays,accountancy_code,base_importe,fk_aplicacion_impuestos";
        $sql .= ",active)";
        $sql .= " VALUES(";

        // List of values
        if (!in_array("id", $listfieldinsert)) {
            $sql .= $newid . ",";
        }
        $i = 0;
        foreach ($listfieldinsert as $f => $value) {
            $keycode = isset($listfieldvalue[$i]) ? $listfieldvalue[$i] : "";
            if (empty($keycode)) {
                $keycode = $value;
            }

            if ($i) {
                $sql .= ",";
            }

            $sql .= "'" . $db->escape(GETPOST($keycode, "alphanohtml")) . "'";

            $i++;
        }
        $sql .= ",1)";

        dol_syslog("actionadd", LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            // Add is ok
            setEventMessages(
                $langs->transnoentities("RecordCreatedSuccessfully"),
                null,
                "mesgs"
            );

            // Clean $_POST array, we keep only id of dictionary
            if ($id == 10 && GETPOST("country", "int") > 0) {
                $search_country_id = GETPOST("country", "int");
            }
            $_POST = ["id" => $id];
        } else {
            if ($db->errno() == "DB_ERROR_RECORD_ALREADY_EXISTS") {
                setEventMessages(
                    $langs->transnoentities("ErrorRecordAlreadyExists"),
                    null,
                    "errors"
                );
            } else {
                dol_print_error($db);
            }
        }
    }

    // If verif ok and action modify, modify the line
    if ($ok && GETPOST("actionmodify")) {
        $tabrowid = "id";
        $rowidcol = $tabrowid;

        // Modify entry
        $sql = "UPDATE " . MAIN_DB_PREFIX . $tablename . " SET ";
        // Modifie valeur des champs
        if (!in_array($tabrowid, $listfieldmodify)) {
            $sql .= $tabrowid . "=";
            $sql .= "'" . $db->escape($rowid) . "', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field) {
            $keycode = $listfieldvalue[$i];
            if (empty($keycode)) {
                $keycode = $field;
            }

            if ($i) {
                $sql .= ",";
            }
            $sql .= $field . "=";

            $sql .= "'" . $db->escape(GETPOST($keycode, "alphanohtml")) . "'";

            $i++;
        }
        if (in_array($rowidcol, ["code", "code_iso"])) {
            $sql .= " WHERE " . $rowidcol . " = '" . $db->escape($rowid) . "'";
        } else {
            $sql .= " WHERE " . $rowidcol . " = " . ((int) $rowid);
        }
        if (in_array("entity", $listfieldmodify)) {
            $sql .= " AND entity = " . ((int) getEntity($tablename, 0));
        }

        dol_syslog("actionmodify", LOG_DEBUG);
        //print $sql;
        $resql = $db->query($sql);
        if (!$resql) {
            setEventMessages($db->error(), null, "errors");
        }
    }

    /*	if (!$ok && GETPOST('actionadd')) {
            $action = 'create';
        }
        if (!$ok && GETPOST('actionmodify')) {
            $action = 'edit';
        }*/
}

if ($action == "confirm_delete" && $confirm == "yes") {
    // delete

    $rowidcol = "id";

    $tablename = "c_chargesociales";
    $tablename = preg_replace(
        "/^" . preg_quote(MAIN_DB_PREFIX, "/") . "/",
        "",
        $tablename
    );

    $sql =
        "DELETE FROM " .
        MAIN_DB_PREFIX .
        $tablename .
        " WHERE " .
        $rowidcol .
        " = " .
        $rowid;

    dol_syslog("delete", LOG_DEBUG);
    $result = $db->query($sql);
    if (!$result) {
        if ($db->errno() == "DB_ERROR_CHILD_EXISTS") {
            setEventMessages(
                $langs->transnoentities("ErrorRecordIsUsedByChild"),
                null,
                "errors"
            );
        } else {
            dol_print_error($db);
        }
    }
}

if ($action == "deletepor") {
    $idpor = GETPOST("idpor");

    $sql = "DELETE FROM llx_porcentaje_impuestos WHERE rowid = " . $idpor;
    $result = $db->query($sql);
    if (!$result) {
        dol_print_error($db);
    }
    $action = "expande";
}

if ($action == $actspor[0]) {
    $idpor = GETPOST("idpor");
    $sql =
        "UPDATE llx_porcentaje_impuestos SET active = 1 WHERE rowid = " .
        $idpor;

    $result = $db->query($sql);
    if (!$result) {
        dol_print_error($db);
    }

    $action = "expande";
}

if ($action == $actspor[1]) {
    $idpor = GETPOST("idpor");
    $sql =
        "UPDATE llx_porcentaje_impuestos SET active = 0 WHERE rowid = " .
        $idpor;

    $result = $db->query($sql);
    if (!$result) {
        dol_print_error($db);
    }
    $action = "expande";
}

if ($action == $acts[0]) {
    $rowidcol = "id";
    $tablename = "c_chargesociales";

    $tablename = preg_replace(
        "/^" . preg_quote(MAIN_DB_PREFIX, "/") . "/",
        "",
        $tablename
    );

    if ($rowid) {
        $sql =
            "UPDATE " .
            MAIN_DB_PREFIX .
            $tablename .
            " SET active = 1 WHERE " .
            $rowidcol .
            " = " .
            $rowid;
    } elseif ($code) {
        $sql =
            "UPDATE " .
            MAIN_DB_PREFIX .
            $tablename .
            " SET active = 1 WHERE code = '" .
            $code .
            "'";
    }

    $result = $db->query($sql);
    if (!$result) {
        dol_print_error($db);
    }
}

if ($action == $acts[1]) {
    $rowidcol = "id";

    $tablename = "c_chargesociales";
    $tablename = preg_replace(
        "/^" . preg_quote(MAIN_DB_PREFIX, "/") . "/",
        "",
        $tablename
    );

    if ($rowid) {
        $sql =
            "UPDATE " .
            MAIN_DB_PREFIX .
            $tablename .
            " SET active = 0 WHERE " .
            $rowidcol .
            " = " .
            $rowid;
    } elseif ($code) {
        $sql =
            "UPDATE " .
            MAIN_DB_PREFIX .
            $tablename .
            " SET active = 0 WHERE code = '" .
            $code .
            "'";
    }

    $result = $db->query($sql);
    if (!$result) {
        dol_print_error($db);
    }
}

// Inicializa el objeto técnico para gestionar los enlaces de la página. Tenga en cuenta que conf->hooks_modules contiene una matriz de contexto de enlace
$hookmanager->initHooks(["admin", "dictionaryadmin"]);

$allowed = $user->admin;
if ($user->hasRight("accounting", "chartofaccount")) {
    $allowed = 1; // Tax page allowed to manager of chart account
}

if (!$allowed) {
    accessforbidden();
}

$permissiontoadd = $allowed;

llxHeader("", $title);

if ($action == "delete") {
    print $form->formconfirm(
        $_SERVER["PHP_SELF"] .
        "?" .
        ($page ? "page=" . $page . "&" : "") .
        "rowid=" .
        urlencode($rowid) .
        "&code=" .
        urlencode($code) .
        $paramwithsearch,
        $langs->trans("DeleteLine"),
        $langs->trans("ConfirmDeleteLine"),
        "confirm_delete",
        "",
        0,
        1
    );
}

$linkback =
    '<a href="' .
    $_SERVER["PHP_SELF"] .
    '">' .
    $langs->trans("BackToDictionaryList") .
    "</a>";

$param = "&id=7";

if (
    $search_country_id ||
    GETPOSTISSET("page") ||
    GETPOST("button_removefilter", "alpha") ||
    GETPOST("button_removefilter.x", "alpha") ||
    GETPOST("button_removefilter_x", "alpha")
) {
    $param .=
        "&search_country_id=" .
        urlencode($search_country_id ? $search_country_id : -1);
}
if ($search_code != "") {
    $param .= "&search_code=" . urlencode($search_code);
}
if ($search_active != "") {
    $param .= "&search_active=" . urlencode($search_active);
}
if ($entity != "") {
    $param .= "&entity=" . (int) $entity;
}
$paramwithsearch = $param;
if ($sortorder) {
    $paramwithsearch .= "&sortorder=" . urlencode($sortorder);
}
if ($sortfield) {
    $paramwithsearch .= "&sortfield=" . urlencode($sortfield);
}
if (GETPOST("from")) {
    $paramwithsearch .= "&from=" . urlencode(GETPOST("from", "alpha"));
}

/*
$sql = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.accountancy_code as accountancy_code, c.code as country_code, c.label as country, a.fk_pays as country_id, a.active, a.base_importe
 FROM " . MAIN_DB_PREFIX . "c_chargesociales AS a, " . MAIN_DB_PREFIX . "c_country as c WHERE a.fk_pays = c.rowid and c.active = 1";
*/

$sql =
    "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.accountancy_code as accountancy_code, c.code as country_code, c.label as country, a.fk_pays as country_id, a.active,  b.nombre as base_importe, a.base_importe as id_base_importe,fk_aplicacion_impuestos";
$sql .= " FROM llx_c_chargesociales AS a";
$sql .= " INNER JOIN llx_c_country as c ON a.fk_pays = c.rowid";
$sql .= " LEFT JOIN llx_base_importe AS b ON a.base_importe=b.rowid";
$sql .= " WHERE a.fk_pays = c.rowid and c.active = 1";

/*
WHERE  c.active = 1 AND c.rowid = 70 
*/

$tablecode = "a.code";

if (!preg_match("/ WHERE /", $sql)) {
    $sql .= " WHERE 1 = 1";
}
if ($search_country_id > 0) {
    $sql .= " AND c.rowid = " . ((int) $search_country_id);
}
if ($search_code != "") {
    $sql .= natural_search($tablecode, $search_code);
}
if ($search_active == "yes") {
    $sql .= " AND  a.active = 1";
} elseif ($search_active == "no") {
    $sql .= " AND a.active = 0";
}

$resql = $db->query($sql);

$fieldlist = explode(",", "code,libelle,country,fk_aplicacion_impuestos,accountancy_code,base_importe");

print '<form action="' .
    $_SERVER["PHP_SELF"] .
    "?id=" .
    $id .
    '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="from" value="' .
    dol_escape_htmltag(GETPOST("from", "alpha")) .
    '">';

if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;

    $massactionbutton = $linkback;

    $newcardbutton = "";
    /*$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
    $newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
    $newcardbutton .= dolGetButtonTitleSeparator();
    */
    $newcardbutton .= dolGetButtonTitle(
        $langs->trans("New"),
        "",
        "fa fa-plus-circle",
        DOL_URL_ROOT .
        "/custom/impuestos/configuracion.php?action=create" .
        $param .
        "&backtopage=" .
        urlencode($_SERVER["PHP_SELF"]),
        "",
        $permissiontoadd
    );

    print_barre_liste(
        "Configuración de Impuesto",
        $page,
        $_SERVER["PHP_SELF"],
        $param,
        $sortfield,
        $sortorder,
        "",
        $num,
        $nbtotalofrecords,
        "tools",
        0,
        $newcardbutton,
        "",
        $listlimit,
        1,
        0,
        1
    );

    if ($action == "create") {
        // Form to add a new line
        //$tabname[7] = "c_chargesociales";
        $tabname = "c_chargesociales";
        $withentity = null;

        $fieldlist = explode(",","code,libelle,country,fk_aplicacion_impuestos,accountancy_code,base_importe");

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';

        // Line for title
        print "<!-- line title to add new entry -->";
        $tdsoffields = '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value) {
            if ($value == "entity") {
                $withentity = getEntity($tabname);
                continue;
            }

            // Define field friendly name from its technical name
            $valuetoshow = ucfirst($value); // Par defaut
            $valuetoshow = $langs->trans($valuetoshow); // try to translate
            $class = "";

            if ($value == "code") {
                $valuetoshow = $langs->trans("Code");
                $class = "maxwidth100";
            }
            if ($value == "libelle" || $value == "label") {
                $valuetoshow = $form->textwithtooltip(
                    $langs->trans("Label"),
                    $langs->trans("LabelUsedByDefault"),
                    2,
                    1,
                    img_help(1, "")
                );
            }

            if ($value == "country") {
                if (in_array("region_id", $fieldlist)) {
                    //print '<td>&nbsp;</td>';
                    continue;
                } // For region page, we do not show the country input
                $valuetoshow = $langs->trans("Country");
            }

            if ($value == "accountancy_code") {
                $valuetoshow = $langs->trans("AccountancyCode");
            }

           

            if ($value == "base_importe") {
                $valuetoshow = "Base importe";
            }

            if ($value == "fk_aplicacion_impuestos") {
                $valuetoshow = "Aplicación impuesto";
            }

            if ($valuetoshow != "") {
                $tooltiphelp = isset(
                    $tabcomplete[$tabname[$id]]["help"][$value]
                )
                    ? $tabcomplete[$tabname[$id]]["help"][$value]
                    : "";

                $tdsoffields .=
                    "<th" . ($class ? ' class="' . $class . '"' : "") . ">";
                if ($tooltiphelp && preg_match("/^http(s*):/i", $tooltiphelp)) {
                    $tdsoffields .=
                        '<a href="' .
                        $tooltiphelp .
                        '" target="_blank">' .
                        $valuetoshow .
                        " " .
                        img_help(1, $valuetoshow) .
                        "</a>";
                } elseif ($tooltiphelp) {
                    $tdsoffields .= $form->textwithpicto(
                        $valuetoshow,
                        $tooltiphelp
                    );
                } else {
                    $tdsoffields .= $valuetoshow;
                }
                $tdsoffields .= "</th>";
            }
        }

        $tdsoffields .= "<th>";
        $tdsoffields .= '<input type="hidden" name="id" value="' . $id . '">';
        if (!is_null($withentity)) {
            $tdsoffields .=
                '<input type="hidden" name="entity" value="' .
                $withentity .
                '">';
        }
        $tdsoffields .= "</th>";
        $tdsoffields .= '<th style="min-width: 26px;"></th>';
        $tdsoffields .= '<th style="min-width: 26px;"></th>';
        $tdsoffields .= "</tr>";

        print $tdsoffields;

        // Line to enter new values
        print "<!-- line input to add new entry -->";
        print '<tr class="oddeven nodrag nodrop nohover">';

        $obj = new stdClass();
        // If data was already input, we define them in obj to populate input fields.
        if (GETPOST("actionadd")) {
            foreach ($fieldlist as $key => $val) {
                if (GETPOST($val) != "") {
                    $obj->$val = GETPOST($val);
                }
            }
        }

        $tmpaction = "create";
        $parameters = ["fieldlist" => $fieldlist, "tabname" => $tabname];
        $reshook = $hookmanager->executeHooks(
            "createDictionaryFieldlist",
            $parameters,
            $obj,
            $tmpaction
        ); // Note that $action and $object may have been modified by some hooks
        $error = $hookmanager->error;
        $errors = $hookmanager->errors;

        if ($id == 3) {
            unset($fieldlist[2]); // Remove field ??? if dictionary Regions
        }

        if (empty($reshook)) {
            fieldList($fieldlist, $obj, $tabname, "add");
        }

        if ($id == 4) {
            print "<td></td>";
            print "<td></td>";
        }
        print '<td colspan="3" class="center">';
        if ($action != "edit") {
            print '<input type="submit" class="button button-add small" name="actionadd" value="' .
                $langs->trans("Add") .
                '">';
        } else {
            print '<input type="submit" class="button button-add small disabled" name="actionadd" value="' .
                $langs->trans("Add") .
                '">';
        }
        print "</td>";

        print "</tr>";

        print "</table>";
        print "</div>";

        print "</form>";

        /*print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
              print '<input type="hidden" name="token" value="'.newToken().'">';
              print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';*/
    }

    $filterfound = 0;
    foreach ($fieldlist as $field => $value) {
        if ($value == "entity") {
            continue;
        }

        $showfield = 1; // By default
        if ($value == "region_id" || $value == "country_id") {
            $showfield = 0;
        }

        if ($showfield) {
            if ($value == "country") {
                $filterfound++;
            } elseif ($value == "code") {
                $filterfound++;
            }
        }
    }

    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';

    $colspan = 0;

    // Title line with search input fields
    print "<!-- line title to search record -->" . "\n";
    print '<tr class="liste_titre_filter">';

    foreach ($fieldlist as $field => $value) {
        if ($value == "entity") {
            continue;
        }

        $showfield = 1; // By default
        if ($value == "region_id" || $value == "country_id") {
            $showfield = 0;
        }

        if ($showfield) {
            if ($value == "country") {
                print '<td class="liste_titre">';
                print $form->select_country(
                    $search_country_id,
                    "search_country_id",
                    "",
                    28,
                    "minwidth100 maxwidth150 maxwidthonsmartphone",
                    "",
                    "&nbsp;"
                );
                print "</td>";
                $colspan++;
            } elseif ($value == "code") {
                print '<td class="liste_titre">';
                print '<input type="text" class="maxwidth100" name="search_code" value="' .
                    dol_escape_htmltag($search_code) .
                    '">';
                print "</td>";
                $colspan++;
            } else {
                print '<td class="liste_titre">';
                print "</td>";
                $colspan++;
            }
        }
    }

    print '<td class="liste_titre center">';
    print $form->selectyesno("search_active", $search_active, 0, false, 1);
    print "</td>";
    $colspan++;

    // Action button
    if (!getDolGlobalString("MAIN_CHECKBOX_LEFT_COLUMN")) {
        print '<td class="liste_titre center">';
        if ($filterfound) {
            $searchpicto = $form->showFilterAndCheckAddButtons(0);
            print $searchpicto;
        }
        print "</td>";
        $colspan++;
    }

    print "</tr>";

    // Title of lines
    print "<!-- line title of record -->" . "\n";
    print '<tr class="liste_titre">';

    // Action button
    if (getDolGlobalString("MAIN_CHECKBOX_LEFT_COLUMN")) {
        print getTitleFieldOfList("");
    }

    foreach ($fieldlist as $field => $value) {
        if ($value == "entity") {
            continue;
        }

        if (
            in_array($value, ["label", "libelle", "libelle_facture"]) &&
            empty($tabcomplete[$tabname[$id]]["help"][$value])
        ) {
            if (!is_array($tabcomplete[$tabname[$id]]["help"])) {
                // protection when $tabcomplete[$tabname[$id]]['help'] is a an empty string, we must force it into an array
                $tabcomplete[$tabname[$id]]["help"] = [];
            }
            $tabcomplete[$tabname[$id]]["help"][$value] = $langs->trans(
                "LabelUsedByDefault"
            );
        }

        // Determines the name of the field in relation to the possible names
        // in data dictionaries
        $showfield = 1; // By default
        $cssprefix = "";
        $sortable = 1;
        $valuetoshow = ucfirst($value); // By default
        $valuetoshow = $langs->trans($valuetoshow); // try to translate

        // Special cases
        if ($value == "source") {
            $valuetoshow = $langs->trans("Contact");
        }
        if ($value == "price") {
            $valuetoshow = $langs->trans("PriceUHT");
        }

        

        if ($value == "fk_aplicacion_impuestos") {
            $valuetoshow = "Aplicación impuesto";
        }

        if ($value == "base_importe") {
            $valuetoshow = "Base Importe";
        }

       

        

        if ($value == "taux") {
            if ($tabname[$id] != "c_revenuestamp") {
                $valuetoshow = $langs->trans("Rate");
            } else {
                $valuetoshow = $langs->trans("Amount");
            }
            $cssprefix = "center ";
        }

        if ($value == "localtax1_type") {
            $valuetoshow = $langs->trans("UseLocalTax") . " 2";
            $cssprefix = "center ";
            $sortable = 0;
        }
        if ($value == "localtax1") {
            $valuetoshow = $langs->trans("RateOfTaxN", "2");
            $cssprefix = "center ";
            $sortable = 0;
        }
        if ($value == "localtax2_type") {
            $valuetoshow = $langs->trans("UseLocalTax") . " 3";
            $cssprefix = "center ";
            $sortable = 0;
        }
        if ($value == "localtax2") {
            $valuetoshow = $langs->trans("RateOfTaxN", "3");
            $cssprefix = "center ";
            $sortable = 0;
        }
        if ($value == "organization") {
            $valuetoshow = $langs->trans("Organization");
        }
        if ($value == "lang") {
            $valuetoshow = $langs->trans("Language");
        }
        if ($value == "type") {
            $valuetoshow = $langs->trans("Type");
        }
        if ($value == "code") {
            $valuetoshow = $langs->trans("Code");
        }
        if (in_array($value, ["pos", "position"])) {
            $valuetoshow = $langs->trans("Position");
            $cssprefix = "right ";
        }
        if ($value == "libelle" || $value == "label") {
            $valuetoshow = $langs->trans("Label");
        }
        if ($value == "libelle_facture") {
            $valuetoshow = $langs->trans("LabelOnDocuments");
        }
        if ($value == "deposit_percent") {
            $valuetoshow = $langs->trans("DepositPercent");
            $cssprefix = "right ";
        }
        if ($value == "country") {
            $valuetoshow = $langs->trans("Country");
        }
        if ($value == "recuperableonly") {
            $valuetoshow = $langs->trans("NPR");
            $cssprefix = "center ";
        }
        if ($value == "nbjour") {
            $valuetoshow = $langs->trans("NbOfDays");
            $cssprefix = "right ";
        }
        if ($value == "type_cdr") {
            $valuetoshow = $langs->trans("AtEndOfMonth");
            $cssprefix = "center ";
        }
        if ($value == "decalage") {
            $valuetoshow = $langs->trans("Offset");
            $cssprefix = "right ";
        }
        if ($value == "width" || $value == "nx") {
            $valuetoshow = $langs->trans("Width");
        }
        if ($value == "height" || $value == "ny") {
            $valuetoshow = $langs->trans("Height");
        }
        if ($value == "unit" || $value == "metric") {
            $valuetoshow = $langs->trans("MeasuringUnit");
        }
        if ($value == "accountancy_code") {
            $valuetoshow = $langs->trans("AccountancyCode");
        }
        if ($value == "accountancy_code_sell") {
            $valuetoshow = $langs->trans("AccountancyCodeSell");
            $sortable = 0;
        }
        if ($value == "accountancy_code_buy") {
            $valuetoshow = $langs->trans("AccountancyCodeBuy");
            $sortable = 0;
        }
        if ($value == "fk_pcg_version") {
            $valuetoshow = $langs->trans("Pcg_version");
        }
        if ($value == "account_parent") {
            $valuetoshow = $langs->trans("Accountsparent");
        }
        if ($value == "pcg_type") {
            $valuetoshow = $langs->trans("Pcg_type");
        }
        if ($value == "pcg_subtype") {
            $valuetoshow = $langs->trans("Pcg_subtype");
        }
        if ($value == "sortorder") {
            $valuetoshow = $langs->trans("SortOrder");
            $cssprefix = "center ";
        }
        if ($value == "short_label") {
            $valuetoshow = $langs->trans("ShortLabel");
        }
        if ($value == "fk_parent") {
            $valuetoshow = $langs->trans("ParentID");
            $cssprefix = "center ";
        }
        if ($value == "range_account") {
            $valuetoshow = $langs->trans("Range");
        }
        if ($value == "sens") {
            $valuetoshow = $langs->trans("Sens");
        }
        if ($value == "category_type") {
            $valuetoshow = $langs->trans("Calculated");
        }
        if ($value == "formula") {
            $valuetoshow = $langs->trans("Formula");
        }
        if ($value == "paper_size") {
            $valuetoshow = $langs->trans("PaperSize");
        }
        if ($value == "orientation") {
            $valuetoshow = $langs->trans("Orientation");
        }
        if ($value == "leftmargin") {
            $valuetoshow = $langs->trans("LeftMargin");
        }
        if ($value == "topmargin") {
            $valuetoshow = $langs->trans("TopMargin");
        }
        if ($value == "spacex") {
            $valuetoshow = $langs->trans("SpaceX");
        }
        if ($value == "spacey") {
            $valuetoshow = $langs->trans("SpaceY");
        }
        if ($value == "font_size") {
            $valuetoshow = $langs->trans("FontSize");
        }
        if ($value == "custom_x") {
            $valuetoshow = $langs->trans("CustomX");
        }
        if ($value == "custom_y") {
            $valuetoshow = $langs->trans("CustomY");
        }
        if ($value == "percent") {
            $valuetoshow = $langs->trans("Percentage");
        }
        if ($value == "affect") {
            $valuetoshow = $langs->trans("WithCounter");
        }
        if ($value == "delay") {
            $valuetoshow = $langs->trans("NoticePeriod");
        }
        if ($value == "newbymonth") {
            $valuetoshow = $langs->trans("NewByMonth");
        }
        if ($value == "fk_tva") {
            $valuetoshow = $langs->trans("VAT");
        }
        if ($value == "range_ik") {
            $valuetoshow = $langs->trans("RangeIk");
        }
        if ($value == "fk_c_exp_tax_cat") {
            $valuetoshow = $langs->trans("CarCategory");
        }
        if ($value == "revenuestamp_type") {
            $valuetoshow = $langs->trans("TypeOfRevenueStamp");
        }
        if ($value == "use_default") {
            $valuetoshow = $langs->trans("Default");
            $cssprefix = "center ";
        }
        if ($value == "unit_type") {
            $valuetoshow = $langs->trans("TypeOfUnit");
        }
        if ($value == "public" && $tablib[$id] == "TicketDictCategory") {
            $valuetoshow = $langs->trans("TicketGroupIsPublic");
            $cssprefix = "center ";
        }
        if ($value == "block_if_negative") {
            $valuetoshow = $langs->trans("BlockHolidayIfNegative");
        }
        if ($value == "type_duration") {
            $valuetoshow = $langs->trans("Unit");
        }

        if ($value == "region_id" || $value == "country_id") {
            $showfield = 0;
        }

        // Show field title
        if ($showfield) {
            $tooltiphelp = isset($tabcomplete[$tabname[$id]]["help"][$value])
                ? $tabcomplete[$tabname[$id]]["help"][$value]
                : "";

            if ($tooltiphelp && preg_match("/^http(s*):/i", $tooltiphelp)) {
                $newvaluetoshow ='<a href="' .$tooltiphelp .'" target="_blank">' .$valuetoshow ." " .img_help(1, $valuetoshow) ."</a>";
            } elseif ($tooltiphelp) {
                $newvaluetoshow = $form->textwithpicto(
                    $valuetoshow,
                    $tooltiphelp
                );
            } else {
                $newvaluetoshow = $valuetoshow;
            }

            print getTitleFieldOfList(
                $newvaluetoshow,
                0,
                $_SERVER["PHP_SELF"],
                $sortable ? $value : "",
                $page ? "page=" . $page . "&" : "",
                $param,
                "",
                $sortfield,
                $sortorder,
                $cssprefix
            );
        }
    }

    // Status
    print getTitleFieldOfList(
        $langs->trans("Status"),
        0,
        $_SERVER["PHP_SELF"],
        "active",
        $page ? "page=" . $page . "&" : "",
        $param,
        'align="center"',
        $sortfield,
        $sortorder
    );

    // Action button
    if (!getDolGlobalString("MAIN_CHECKBOX_LEFT_COLUMN")) {
        print getTitleFieldOfList("");
    }
    print "</tr>";

    if ($num) {
        // Lines with values
        while ($i < $num) {
            $obj = $db->fetch_object($resql);

            $withentity = null;

            // We discard empty lines
            /*if ($id == 4) {
                            if ($obj->code == '') {
                                $i++;
                                continue;
                            }
                        }*/

            // Can an entry be erased, disabled or modified ? (true by default)
            $iserasable = 1;
            $canbedisabled = 1;
            $canbemodified = 1;
            if (isset($obj->code) && $id != 10 && $id != 42) {
                if (
                    $obj->code == "0" ||
                    $obj->code == "" ||
                    preg_match("/unknown/i", $obj->code)
                ) {
                    $iserasable = 0;
                    $canbedisabled = 0;
                } elseif ($obj->code == "RECEP") {
                    $iserasable = 0;
                    $canbedisabled = 0;
                } elseif ($obj->code == "EF0") {
                    $iserasable = 0;
                    $canbedisabled = 0;
                }
            }
            /*if ($id == 25 && in_array($obj->code, array('banner', 'blogpost', 'menu', 'page', 'other'))) {
                            $iserasable = 0;
                            $canbedisabled = 0;
                            if (in_array($obj->code, array('banner'))) {
                                $canbedisabled = 1;
                            }
                        }*/
            if (
                isset($obj->type) &&
                in_array($obj->type, ["system", "systemauto"])
            ) {
                $iserasable = 0;
            }
            if (
                in_array(empty($obj->code) ? "" : $obj->code, [
                    "AC_OTH",
                    "AC_OTH_AUTO",
                ]) ||
                in_array(empty($obj->type) ? "" : $obj->type, ["systemauto"])
            ) {
                $canbedisabled = 0;
                $canbedisabled = 0;
            }
            $canbemodified = $iserasable;

            if (!empty($obj->code) && $obj->code == "RECEP") {
                $canbemodified = 1;
            }
            if ($tabname[$id] == "c_actioncomm") {
                $canbemodified = 1;
            }

            // Build Url. The table is id=, the id of line is rowid=
            $rowidcol = empty($tabrowid[$id]) ? "rowid" : $tabrowid[$id];
            // If rowidcol not defined
            if (
                empty($rowidcol) ||
                in_array($id, [6, 7, 8, 13, 17, 19, 27, 32])
            ) {
                $rowidcol = "rowid";
            }
            $url =
                $_SERVER["PHP_SELF"] .
                "?" .
                ($page ? "page=" . $page . "&" : "") .
                "sortfield=" .
                $sortfield .
                "&sortorder=" .
                $sortorder;
            $url .=
                "&rowid=" .
                (isset($obj->$rowidcol)
                    ? $obj->$rowidcol
                    : (!empty($obj->code)
                        ? urlencode($obj->code)
                        : ""));
            $url .=
                "&code=" . (!empty($obj->code) ? urlencode($obj->code) : "");
            if (!empty($param)) {
                $url .= "&" . $param;
            }
            if (!is_null($withentity)) {
                $url .= "&entity=" . $withentity;
            }
            $url .= "&";

            //print_r($obj);
            print '<tr class="oddeven" id="rowid-' .
                (empty($obj->rowid) ? "" : $obj->rowid) .
                '">';

            // Action button
            if (getDolGlobalString("MAIN_CHECKBOX_LEFT_COLUMN")) {
                print '<td class="center maxwidthsearch nowraponall">';
                // Modify link
                if ($canbemodified) {
                    print '<a class="reposition editfielda marginleftonly paddingleft marginrightonly paddingright" href="' .
                        $url .
                        "action=edit&token=" .
                        newToken() .
                        '">' .
                        img_edit() .
                        "</a>";
                }
                // Delete link
                if ($iserasable) {
                    if ($user->admin) {
                        print '<a class="reposition marginleftonly paddingleft marginrightonly paddingright" href="' .
                            $url .
                            "action=delete&token=" .
                            newToken() .
                            '">' .
                            img_delete() .
                            "</a>";
                    }
                }
                print "</td>";
            }

            if (
                $action == "edit" &&
                $rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code)
            ) {
                $tmpaction = "edit";
                $parameters = [
                    "fieldlist" => $fieldlist,
                    "tabname" => $tabname[$id],
                ];
                $reshook = $hookmanager->executeHooks("editDictionaryFieldlist",$parameters,$obj,$tmpaction); // Note that $action and $object may have been modified by some hooks
                $error = $hookmanager->error;
                $errors = $hookmanager->errors;

                // Show fields
                if (empty($reshook)) {
                    $withentity = fieldList($fieldlist,$obj,$tabname[$id],"edit");
                }

                print '<td colspan="3" class="center">';
                print '<div name="' .
                    (!empty($obj->rowid) ? $obj->rowid : $obj->code) .
                    '"></div>';
                print '<input type="hidden" name="page" value="' .
                    dol_escape_htmltag($page) .
                    '">';
                print '<input type="hidden" name="rowid" value="' .
                    dol_escape_htmltag($rowid) .
                    '">';
                if (!is_null($withentity)) {
                    print '<input type="hidden" name="entity" value="' .
                        $withentity .
                        '">';
                }
                print '<input type="submit" class="button button-edit small" name="actionmodify" value="' .
                    $langs->trans("Modify") .
                    '">';
                print '<input type="submit" class="button button-cancel small" name="actioncancel" value="' .
                    $langs->trans("Cancel") .
                    '">';
                print "</td>";
            } else {
                $tmpaction = "view";
                $parameters = [
                    "fieldlist" => $fieldlist,
                    "tabname" => $tabname[$id],
                ];
                $reshook = $hookmanager->executeHooks(
                    "viewDictionaryFieldlist",
                    $parameters,
                    $obj,
                    $tmpaction
                ); // Note that $action and $object may have been modified by some hooks

                $error = $hookmanager->error;
                $errors = $hookmanager->errors;

                if (empty($reshook)) {
                    $withentity = null;

                    foreach ($fieldlist as $field => $value) {
                        //var_dump($fieldlist);
                        $class = "";
                        $showfield = 1;
                        $valuetoshow = empty($obj->$value) ? "" : $obj->$value;
                        $titletoshow = "";

                        if ($value == "entity") {
                            $withentity = $valuetoshow;
                            continue;
                        }

                        if ($value == "element") {
                            $valuetoshow = isset($elementList[$valuetoshow])
                                ? $elementList[$valuetoshow]
                                : $valuetoshow;
                        } elseif ($value == "source") {
                            $valuetoshow = isset($sourceList[$valuetoshow])
                                ? $sourceList[$valuetoshow]
                                : $valuetoshow;
                        } elseif ($valuetoshow == "all") {
                            $valuetoshow = $langs->trans("All");
                        } elseif ($value == "country") {
                            if (empty($obj->country_code)) {
                                $valuetoshow = "-";
                            } else {
                                $key = $langs->trans("Country" . strtoupper($obj->country_code));
                                $valuetoshow =
                                    $key !="Country" . strtoupper($obj->country_code)
                                    ? $obj->country_code . " - " . $key: $obj->country;
                            }
                        } elseif (
                            $value == "recuperableonly" ||
                            $value == "deductible" ||
                            $value == "category_type"
                        ) {
                            $valuetoshow = yn($valuetoshow ? 1 : 0);
                            $class = "center";
                        } elseif ($value == "type_cdr") {
                            if (empty($valuetoshow)) {
                                $valuetoshow = $langs->trans("None");
                            } elseif ($valuetoshow == 1) {
                                $valuetoshow = $langs->trans("AtEndOfMonth");
                            } elseif ($valuetoshow == 2) {
                                $valuetoshow = $langs->trans("CurrentNext");
                            }
                            $class = "center";
                        } elseif (
                            $value == "price" ||
                            preg_match("/^amount/i", $value)
                        ) {
                            $valuetoshow = price($valuetoshow);
                        }
                        if ($value == "private") {
                            $valuetoshow = yn($valuetoshow);
                        } elseif ($value == "libelle_facture") {
                            $langs->load("bills");
                            $key = $langs->trans(
                                "PaymentCondition" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key !=
                                "PaymentCondition" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                            $valuetoshow = nl2br($valuetoshow);
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_country"
                        ) {
                            $key = $langs->trans(
                                "Country" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "Country" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_availability"
                        ) {
                            $langs->load("propal");
                            $key = $langs->trans(
                                "AvailabilityType" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key !=
                                "AvailabilityType" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_actioncomm"
                        ) {
                            $key = $langs->trans(
                                "Action" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "Action" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            !empty($obj->code_iso) &&
                            $value == "label" &&
                            $tabname[$id] == "c_currencies"
                        ) {
                            $key = $langs->trans(
                                "Currency" . strtoupper($obj->code_iso)
                            );
                            $valuetoshow =
                                $obj->code_iso &&
                                $key != "Currency" . strtoupper($obj->code_iso)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_typent"
                        ) {
                            $key = $langs->trans(strtoupper($obj->code));
                            $valuetoshow =
                                $key != strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_prospectlevel"
                        ) {
                            $key = $langs->trans(strtoupper($obj->code));
                            $valuetoshow =
                                $key != strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_civility"
                        ) {
                            $key = $langs->trans(
                                "Civility" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "Civility" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_type_contact"
                        ) {
                            $langs->load("agenda");
                            $key = $langs->trans(
                                "TypeContact_" .
                                $obj->element .
                                "_" .
                                $obj->source .
                                "_" .
                                strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key !=
                                "TypeContact_" .
                                $obj->element .
                                "_" .
                                $obj->source .
                                "_" .
                                strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_payment_term"
                        ) {
                            $langs->load("bills");
                            $key = $langs->trans(
                                "PaymentConditionShort" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key !=
                                "PaymentConditionShort" .
                                strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_paiement"
                        ) {
                            $langs->load("bills");
                            $key = $langs->trans(
                                "PaymentType" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "PaymentType" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "type" &&
                            $tabname[$id] == "c_paiement"
                        ) {
                            $payment_type_list = [
                                0 => $langs->trans("PaymentTypeCustomer"),
                                1 => $langs->trans("PaymentTypeSupplier"),
                                2 => $langs->trans("PaymentTypeBoth"),
                            ];
                            $valuetoshow = $payment_type_list[$valuetoshow];
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_input_reason"
                        ) {
                            $key = $langs->trans(
                                "DemandReasonType" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key !=
                                "DemandReasonType" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_input_method"
                        ) {
                            $langs->load("orders");
                            $key = $langs->trans($obj->code);
                            $valuetoshow =
                                $obj->code && $key != $obj->code
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_shipment_mode"
                        ) {
                            $langs->load("sendings");
                            $key = $langs->trans(
                                "SendingMethod" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "SendingMethod" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "libelle" &&
                            $tabname[$id] == "c_paper_format"
                        ) {
                            $key = $langs->trans(
                                "PaperFormat" . strtoupper($obj->code)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "PaperFormat" . strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_type_fees"
                        ) {
                            $langs->load("trips");
                            $key = $langs->trans(strtoupper($obj->code));
                            $valuetoshow =
                                $obj->code && $key != strtoupper($obj->code)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "region_id" ||
                            $value == "country_id"
                        ) {
                            $showfield = 0;
                        } elseif ($value == "unicode") {
                            $valuetoshow = $langs->getCurrencySymbol(
                                $obj->code,
                                1
                            );
                        } elseif (
                            $value == "label" &&
                            $tabname[GETPOST("id", "int")] == "c_units"
                        ) {
                            $langs->load("products");
                            $valuetoshow = $langs->trans($obj->$value);
                        } elseif (
                            $value == "short_label" &&
                            $tabname[GETPOST("id", "int")] == "c_units"
                        ) {
                            $langs->load("products");
                            $valuetoshow = $langs->trans($obj->$value);
                        } elseif (
                            $value == "unit" &&
                            $tabname[$id] == "c_paper_format"
                        ) {
                            $key = $langs->trans(
                                "SizeUnit" . strtolower($obj->unit)
                            );
                            $valuetoshow =
                                $obj->code &&
                                $key != "SizeUnit" . strtolower($obj->unit)
                                ? $key
                                : $obj->$value;
                        } elseif (
                            $value == "localtax1" ||
                            $value == "localtax2"
                        ) {
                            $class = "center";
                        } elseif ($value == "localtax1_type") {
                            if ($obj->localtax1 != 0) {
                                $valuetoshow = $localtax_typeList[$valuetoshow];
                            } else {
                                $valuetoshow = "";
                            }
                            $class = "center";
                        } elseif ($value == "localtax2_type") {
                            if ($obj->localtax2 != 0) {
                                $valuetoshow = $localtax_typeList[$valuetoshow];
                            } else {
                                $valuetoshow = "";
                            }
                            $class = "center";
                        } elseif ($value == "taux") {
                            $valuetoshow = price($valuetoshow, 0, $langs, 0, 0);
                            $class = "center";
                        } elseif (in_array($value, ["recuperableonly"])) {
                            $class = "center";
                        } elseif (
                            $value == "accountancy_code" ||
                            $value == "accountancy_code_sell" ||
                            $value == "accountancy_code_buy"
                        ) {
                            if (isModEnabled("accounting")) {
                                require_once DOL_DOCUMENT_ROOT .
                                    "/accountancy/class/accountingaccount.class.php";
                                $tmpaccountingaccount = new AccountingAccount(
                                    $db
                                );
                                $tmpaccountingaccount->fetch(
                                    0,
                                    $valuetoshow,
                                    1
                                );
                                $titletoshow =
                                    $langs->transnoentitiesnoconv("Pcgtype") .
                                    ": " .
                                    $tmpaccountingaccount->pcg_type;
                            }
                            $valuetoshow = length_accountg($valuetoshow);
                        } elseif ($value == "fk_tva") {
                            foreach ($form->cache_vatrates as $key => $Tab) {
                                if (
                                    $form->cache_vatrates[$key]["rowid"] ==
                                    $valuetoshow
                                ) {
                                    $valuetoshow =
                                        $form->cache_vatrates[$key]["label"];
                                    break;
                                }
                            }
                        } elseif ($value == "fk_c_exp_tax_cat") {
                            $tmpid = $valuetoshow;
                            $valuetoshow = getDictionaryValue(
                                "c_exp_tax_cat",
                                "label",
                                $tmpid
                            );
                            $valuetoshow = $langs->trans(
                                $valuetoshow ? $valuetoshow : $tmpid
                            );
                        } elseif ($tabname[$id] == "c_exp_tax_cat") {
                            $valuetoshow = $langs->trans($valuetoshow);
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_units"
                        ) {
                            $langs->load("other");
                            $key = $langs->trans($obj->label);
                            $valuetoshow =
                                $obj->label && $key != strtoupper($obj->label)
                                ? $key
                                : $obj->{$value};
                        } elseif (
                            $value == "label" &&
                            $tabname[$id] == "c_product_nature"
                        ) {
                            $langs->load("products");
                            $valuetoshow = $langs->trans($obj->{$value});
                        } elseif (
                            $fieldlist[$field] == "label" &&
                            $tabname[$id] == "c_productbatch_qcstatus"
                        ) {
                            $langs->load("productbatch");
                            $valuetoshow = $langs->trans($obj->{$value});
                        } elseif ($value == "block_if_negative") {
                            $valuetoshow = yn($obj->{$value});
                        } elseif ($value == "icon") {
                            $valuetoshow =
                                $obj->{$value} .
                                " " .
                                img_picto("", $obj->{$value});
                        } elseif ($value == "type_duration") {
                            $TDurationTypes = [
                                "y" => $langs->trans("Years"),
                                "m" => $langs->trans("Month"),
                                "w" => $langs->trans("Weeks"),
                                "d" => $langs->trans("Days"),
                                "h" => $langs->trans("Hours"),
                                "i" => $langs->trans("Minutes"),
                            ];
                            if (
                                !empty($obj->{$value}) &&
                                array_key_exists(
                                    $obj->{$value},
                                    $TDurationTypes
                                )
                            ) {
                                $valuetoshow = $TDurationTypes[$obj->{$value}];
                            }
                        }
                        $class .= ($class ? " " : "") . "tddict";
                        if ($value == "note" && $id == 10) {
                            $class .= " tdoverflowmax200";
                        }
                        if ($value == "tracking") {
                            $class .= " tdoverflowauto";
                        }
                        if (
                            in_array($value, [
                                "nbjour",
                                "decalage",
                                "pos",
                                "position",
                                "deposit_percent",
                            ])
                        ) {
                            $class .= " right";
                        }
                        if (
                            in_array($value, [
                                "localtax1_type",
                                "localtax2_type",
                            ])
                        ) {
                            $class .= " nowraponall";
                        }
                        if (
                            in_array($value, [
                                "use_default",
                                "fk_parent",
                                "sortorder",
                            ])
                        ) {
                            $class .= " center";
                        }
                        if ($value == "public") {
                            $class .= " center";
                        }
                        // Show value for field
                        if ($showfield) {
                            print "<!-- " .
                                $value .
                                ' --><td class="' .
                                $class .
                                '"' .
                                ($titletoshow
                                    ? ' title="' .
                                    dol_escape_htmltag($titletoshow) .
                                    '"'
                                    : "") .
                                ">" .
                                $valuetoshow .
                                "</td>";
                        }
                    }

                    // Favorite & EEC
                    // Only for country dictionary
                    /*if ($id == 4) {
                                          print '<td class="nowrap center">';
                                          // Is in EEC
                                          if ($iserasable) {
                                              print '<a class="reposition" href="'.$url.'action='.$acts[$obj->eec].'_eec&token='.newToken().'">'.$actl[$obj->eec].'</a>';
                                          } else {
                                              print '<span class="opacitymedium">'.$langs->trans("AlwaysActive").'</span>';
                                          }
                                          print '</td>';
                                          print '<td class="nowrap center">';
                                          // Favorite
                                          if ($iserasable) {
                                              print '<a class="reposition" href="'.$url.'action='.$acts[$obj->favorite].'_favorite&token='.newToken().'">'.$actl[$obj->favorite].'</a>';
                                          } else {
                                              print '<span class="opacitymedium">'.$langs->trans("AlwaysActive").'</span>';
                                          }
                                          print '</td>';
                                      }*/
                }

                // Active
                print '<td class="nowrap center">';
                if ($canbedisabled) {
                    print '<a id="active' .
                        $obj->rowid .'" onclick="scrollX(' .
                        $obj->rowid .',\'active\')"  class="reposition" href="' .
                        $url .
                        "action=" .
                        $acts[$obj->active] .
                        "&token=" .
                        newToken() .
                        '">' .
                        $actl[$obj->active] .
                        "</a>";
                } else {
                    if (in_array($obj->code, ["AC_OTH", "AC_OTH_AUTO"])) {
                        print $langs->trans("AlwaysActive");
                    } elseif (
                        isset($obj->type) &&
                        in_array($obj->type, ["systemauto"]) &&
                        empty($obj->active)
                    ) {
                        print $langs->trans("Deprecated");
                    } elseif (
                        isset($obj->type) &&
                        in_array($obj->type, ["system"]) &&
                        !empty($obj->active) &&
                        $obj->code != "AC_OTH"
                    ) {
                        print $langs->trans("UsedOnlyWithTypeOption");
                    } else {
                        print '<span class="opacitymedium">' .
                            $langs->trans("AlwaysActive") .
                            "</span>";
                    }
                }

                print "</td>";

                // Action button
                if (!getDolGlobalString("MAIN_CHECKBOX_LEFT_COLUMN")) {
                    print '<td class="center maxwidthsearch nowraponall">';

                    print '<a id="expande' .
                        $obj->rowid .
                        '" onclick="scrollX(' .
                        $obj->rowid .
                        ',\'expande\')" class="reposition marginleftonly paddingleft marginrightonly paddingright fa fa-caret-down" href="' .
                        $url .
                        "action=expande&token=" .
                        newToken() .
                        '"></a>';
                    // Modify link
                    if ($canbemodified) {
                        print '<a class="reposition marginleftonly paddingleft marginrightonly paddingright editfielda" href="' .
                            $url .
                            "action=edit&token=" .
                            newToken() .
                            '">' .
                            img_edit() .
                            "</a>";
                    }
                    // Delete link
                    if ($iserasable) {
                        if ($user->admin) {
                            print '<a class="reposition marginleftonly paddingleft marginrightonly paddingright" href="' .
                                $url .
                                "action=delete&token=" .
                                newToken() .
                                '">' .
                                img_delete() .
                                "</a>";
                        }
                    }
                    print "</td>";
                }

                print "</tr>\n";

                if (
                    $action == "expande" &&
                    ($obj->rowid == $rowid || $obj->code == $rowid)
                ) {
                    print "<tr>";
                    print '<td colspan="2" >';
                    $newcardbutton = dolGetButtonTitle(
                        $langs->trans("New"),
                        "",
                        "fa fa-plus-circle",
                        DOL_URL_ROOT .
                        "/impuestos/configuracion.php?action=create" .
                        $param .
                        "&backtopage=" .
                        urlencode($_SERVER["PHP_SELF"]),
                        "",
                        $permissiontoadd
                    );

                    $thirdpartygraph =
                        '<div class="div-table-responsive-no-min">';
                    $thirdpartygraph .=
                        '<table class="noborder nohover centpercent">' . "\n";

                    $thirdpartygraph .= '<tr class="liste_titre">';
                    $thirdpartygraph .= '<td class="wrapcolumntitle">';
                    $thirdpartygraph .= " Tarifa (%)</td>";
                    $thirdpartygraph .= "<td>";
                    $thirdpartygraph .= " Estado</td>";
                    $thirdpartygraph .= '<td class="right">';
                    $thirdpartygraph .='<a id="expandeadd' .$obj->rowid .'" onclick="scrollX('.$obj->rowid.',\'expandeadd\')" class="reposition marginleftonly paddingleft marginrightonly paddingright editfielda fa fa-plus-circle" href="' .$url ."action=expande&op=add&token=" . newToken() .'"></a>';
                    $thirdpartygraph .= "</td> </tr>";

                    $op = GETPOST("op", "alpha");
                    if ($op == "add") {
                        print '<input type="hidden" name="saveporsentaje" value="true">';
                        print '<input type="hidden" name="idimpuesto" value="' .$obj->rowid .'">';
                        $thirdpartygraph .= '<tr class="liste_titre">';
                        $thirdpartygraph .= '<td class="wrapcolumntitle">';
                        $thirdpartygraph .='<input type="text" class="flat' .($class ? " " . $class : "") .'"' .($maxlength ? " " . $maxlength : "") .'  name="porcentage">';
                        $thirdpartygraph .= " </td>";
                        $thirdpartygraph .= "<td></td>";
                        $thirdpartygraph .= '<td class="right">';
                        $thirdpartygraph .='<input type="submit" class="button button-add small" name="actionadd" value="' .$langs->trans("Add") .'">';
                        $thirdpartygraph .= "</td> </tr>";
                        print "</form>";
                    }

                    

                    $sql =
                        "SELECT rowid, porcentaje,active FROM llx_porcentaje_impuestos WHERE fk_chargesociales =" .
                        $obj->rowid;

                    $result = $db->query($sql);
                    if ($result) {
                        $j = 0;
                        $numfila = $db->num_rows($result);
                        if ($numfila) {
                            while ($j < $numfila) {
                                $obj = $db->fetch_object($result);

                                $op = GETPOST("op", "alpha");
                                $idpor = GETPOST("idpor", "alpha");
                                if ($op == "editrow" && $idpor == $obj->rowid) {
                                    print '<input type="hidden" name="editporcentaje" value="true">';
                                    print '<input type="hidden" name="idimpuesto" value="' .
                                        $obj->rowid .
                                        '">';

                                    $thirdpartygraph .= "<tr>";
                                    $thirdpartygraph .=
                                        '<td class="wrapcolumntitle">';
                                    $thirdpartygraph .=
                                        '<input type="text" class="flat' .
                                        ($class ? " " . $class : "") .
                                        '"' .
                                        ($maxlength ? " " . $maxlength : "") .
                                        '  name="valueedirpor" value=' .
                                        $obj->porcentaje .
                                        ">";
                                    $thirdpartygraph .= " </td>";
                                    $thirdpartygraph .= '<td class="right">';
                                    $thirdpartygraph .=
                                        '<input type="submit" class="button button-edit small" name="actionmodify" value="' .
                                        $langs->trans("Modify") .
                                        '">';
                                    $thirdpartygraph .= "</td>";
                                    $thirdpartygraph .= '<td class="right">';
                                    $thirdpartygraph .=
                                        '<input type="submit" class="button button-cancel small" name="actioncancel" value="' .
                                        $langs->trans("Cancel") .
                                        '">';
                                    $thirdpartygraph .= "</td> </tr>";
                                    print "</form>";
                                } else {
                                    $thirdpartygraph .= '<tr class="">';
                                    $thirdpartygraph .=
                                        '<td class="tddict tdoverflowmax200"> ' .
                                        $obj->porcentaje .
                                        " %";
                                    $thirdpartygraph .= '</td">';
                                    $thirdpartygraph .= '<td class="nowrap">';
                                    $thirdpartygraph .=
                                        '<a id="activatepor' .
                                        $obj->rowid .
                                        '" onclick="scrollX(' .
                                        $obj->rowid .
                                        ',\'activatepor\')" class="reposition" href="' .
                                        $url .
                                        "action=" .
                                        $actspor[$obj->active] .
                                        "&token=" .
                                        newToken() .
                                        "&idpor=" .
                                        $obj->rowid .
                                        '">' .
                                        $actlpor[$obj->active] .
                                        "</a>";
                                    $thirdpartygraph .= '</td">';
                                    $thirdpartygraph .=
                                        '<td class="center maxwidthsearch nowraponall">';
                                    $thirdpartygraph .=
                                        '<a id="updatepor' .
                                        $obj->rowid .
                                        '" onclick="scrollX(' .
                                        $obj->rowid .
                                        ',\'updatepor\')" class="reposition marginleftonly paddingleft marginrightonly paddingright editfielda" href="' .
                                        $url .
                                        "action=expande&token=" .
                                        newToken() .
                                        "&idpor=" .
                                        $obj->rowid .
                                        '&op=editrow">' .
                                        img_edit() .
                                        "</a>";
                                    $thirdpartygraph .=
                                        '<a id="deletepor' .
                                        $obj->rowid .
                                        '" onclick="scrollX(' .
                                        $obj->rowid .
                                        ',\'deletepor\')" class="reposition marginleftonly paddingleft marginrightonly paddingright" href="' .
                                        $url .
                                        "action=deletepor&token=" .
                                        newToken() .
                                        "&idpor=" .
                                        $obj->rowid .
                                        '">' .
                                        img_delete() .
                                        "</a>";
                                    $thirdpartygraph .= '</td">';
                                    $thirdpartygraph .= "</tr>";
                                }

                                $j++;
                            }
                        }
                    } else {
                        dol_print_error($db);
                    }

                    $thirdpartygraph .= "</table>";
                    $thirdpartygraph .= "</div>";
                    print $thirdpartygraph;
                    print "</td>";
                    print '<td colspan="4" >';
                    print "</td>";
                    print "</tr>\n";
                }
            }
            $i++;
        }
    } else {
        print '<tr><td colspan="' .
            $colspan .
            '"><span class="opacitymedium">' .
            $langs->trans("NoRecordFound") .
            "</span></td></tr>";
    }

    print "</table>";
    print "</div>";
} else {
    dol_print_error($db);
}

function fieldList($fieldlist, $obj = null, $tabname = "", $context = "")
{
    global $conf, $langs, $db, $mysoc;
    global $form;
    global $region_id;
    global $elementList, $sourceList, $localtax_typeList;

    $formadmin = new FormAdmin($db);
    $formcompany = new FormCompany($db);
    $formaccounting = new FormAccounting($db);
    $impuestos = new Impuestos($db);

    $withentity = "";

    foreach ($fieldlist as $field => $value) {
        if ($value == "country") {
            if (in_array("region_id", $fieldlist)) {
                print "<td>";
                print "</td>";
                continue;
            } // For state page, we do not show the country input (we link to region, not country)
            print "<td>";

            $selected = !empty($obj->country_code)
                ? $obj->country_code
                : (!empty($obj->country)
                    ? $obj->country
                    : "");
            if (!GETPOSTISSET("code")) {
                $selected = GETPOST("countryidforinsert");
            }
            print $form->select_country(
                $selected,
                $value,
                "",
                28,
                "minwidth100 maxwidth150 maxwidthonsmartphone"
            );
            print "</td>";
        } elseif (
            $value == "accountancy_code" || $value == "accountancy_code_sell" ||$value == "accountancy_code_buy"
        ) {
            print "<td>";
            if (isModEnabled("accounting")) {
                $fieldname = $value;
                $accountancy_account = !empty($obj->$fieldname)
                    ? $obj->$fieldname
                    : 0;
                print $formaccounting->select_account(
                    $accountancy_account,
                    "." . $value,
                    1,
                    "",
                    1,
                    1,
                    "maxwidth200 maxwidthonsmartphone"
                );
            } else {
                $fieldname = $value;
                print '<input type="text" size="10" class="flat" value="' .
                    (isset($obj->$fieldname) ? $obj->$fieldname : "") .
                    '" name="' .
                    $value .
                    '">';
            }
            print "</td>";
        }
        /* elseif ($value == 'fk_tva') {
            print '<td>';
            print $form->load_tva('fk_tva', $obj->taux, $mysoc, new Societe($db), 0, 0, '', false, -1);
            print '</td>';
        } elseif ($value == 'fk_c_exp_tax_cat') {
            print '<td>';
            print $form->selectExpenseCategories($obj->fk_c_exp_tax_cat);
            print '</td>';
        } elseif ($value == 'fk_range') {
            print '<td>';
            print $form->selectExpenseRanges($obj->fk_range);
            print '</td>';
        } elseif ($value == 'block_if_negative') {
            print '<td>';
            print $form->selectyesno("block_if_negative", (empty($obj->block_if_negative) ? '' : $obj->block_if_negative), 1);
            print '</td>';
        } elseif ($value == 'type_duration') {
            print '<td>';
            print $form->selectTypeDuration('', (empty($obj->type_duration) ? '' : $obj->type_duration), array('i', 'h'));
            print '</td>';
        } */ elseif ( $value == "base_importe") {
            $selected = !empty($obj->id_base_importe)? $obj->id_base_importe: 0;
            print "<td>";
            print $impuestos->getSelectBaseImporte("base_importe",1,"maxwidth200 maxwidthonsmartphone",$selected);
            print "</td>";
        }elseif($value=='fk_aplicacion_impuestos'){
            $selected = !empty($obj->codigo)? $obj->codogo: '';
            print "<td>";
            print $impuestos->getSelectAplicacionImpuesto("fk_aplicacion_impuestos",1,"maxwidth200 maxwidthonsmartphone",$selected);
            print "</td>";


        } 
        
        else {
            $fieldValue = isset($obj->{$value}) ? $obj->{$value} : "";
            $classtd = "";
            $class = "";

            // Labels Length
            $maxlength = "";
            if (in_array($fieldlist[$field], ["libelle", "label"])) {
                switch ($tabname) {
                    case "c_ecotaxe":
                    case "c_email_senderprofile":
                    case "c_forme_juridique":
                    case "c_holiday_types":
                    case "c_payment_term":
                    case "c_transport_mode":
                        $maxlength = ' maxlength="255"';
                        break;
                    case "c_email_templates":
                        $maxlength = ' maxlength="180"';
                        break;
                    case "c_socialnetworks":
                        $maxlength = ' maxlength="150"';
                        break;
                    default:
                        $maxlength = ' maxlength="128"';
                }
            }

            print '<td class="' . $classtd . '">';
            /*$transfound = 0;
                     $transkey = '';
                     if (in_array($fieldlist[$field], array('label', 'libelle'))) {		// For label
                         // Special case for labels
                         if ($tabname == 'c_civility' && !empty($obj->code)) {
                             $transkey = "Civility".strtoupper($obj->code);
                         }
                         if ($tabname == 'c_payment_term' && !empty($obj->code)) {
                             $langs->load("bills");
                             $transkey = "PaymentConditionShort".strtoupper($obj->code);
                         }
                         if ($transkey && $langs->trans($transkey) != $transkey) {
                             $transfound = 1;
                             print $form->textwithpicto($langs->trans($transkey), $langs->trans("GoIntoTranslationMenuToChangeThis"));
                         }
                     }*/
            //if (!$transfound) {
            print '<input type="text" class="flat' .
                ($class ? " " . $class : "") .
                '"' .
                ($maxlength ? " " . $maxlength : "") .
                ' value="' .
                dol_escape_htmltag($fieldValue) .
                '" name="' .
                $fieldlist[$field] .
                '">';
            /*} else {
                         print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$transkey.'">';
                     }*/
            print "</td>";
        }
    }

    return $withentity;
}
?>

<script>




    if (getParameterByName('scrollY') != undefined) {
        var scrolly = getParameterByName('scrollY');

        window.scroll(0, scrolly);

    }



    function scrollX(row, nombre) {
        var div1 = document.getElementById(nombre + row);
        var align = div1.getAttribute("href");
        div1.setAttribute("href", align + "&scrollY=" + window.scrollY);
    }

</script>