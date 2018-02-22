<?php
/*
@author of original dzhuryn https://github.com/dzhuryn
*/
if ($modx->event->name == 'OnManagerPageInit') {
    $M = $modx->getFullTableName('site_modules');
    $MD = $modx->getFullTableName('site_module_depobj');
    $S = $modx->getFullTableName('site_snippets');
    $P = $modx->getFullTableName('site_plugins');
    //id плагина
    //поиск и обновление модуля
    $value  = $modx->db->getValue($modx->db->select('id', $M, 'name="eLists"'));
    $moduleGuid  = $modx->db->getValue($modx->db->select('guid', $M, 'name="eLists"'));
    $moduleId =  $value;
    $fields = array('enable_sharedparams' => 1);
 
    $modx->db->update($fields, $M, 'id = "' . $moduleId . '"');
    $snippets = array('eFilter', 'eFilterResult', 'multiParams', 'tovarParams');
    $plugins = array('eFilterHelper');
    foreach ($snippets as $snippet) {
        $snippetId  = $modx->db->getValue($modx->db->select('id', $S, 'name="' . $snippet . '"'));
        if (empty($snippetId)) {
            continue;
        }
        $value = $modx->db->getValue($modx->db->select('id', $MD, 'resource="' . $snippetId . '" AND module="' . $moduleId . '" AND type=40'));
        if (!empty($value)) {
              continue;
        }
        //запись в site_module_depobj
        $fields = array(
            'module' => $moduleId,
            'resource' => $snippetId,
            'type' => 40
        );
        $modx->db->insert($fields, $MD);
        //добавляем модуль в сниппет
        $fields = array('moduleguid' => $moduleGuid);
        $modx->db->update($fields, $S, 'id = "' . $snippetId . '"');
    }
    foreach ($plugins as $plugin) {
        $pluginId  = $modx->db->getValue($modx->db->select('id', $P, 'name="' . $plugin . '"'));
        if (empty($pluginId)) {
            continue;
        }
        //запись в site_module_depobj
        $value = $modx->db->getValue($modx->db->select('id', $MD, 'resource="' . $pluginId . '" AND module="' . $moduleId . '"  AND type=30'));
        if (!empty($value)) {
            continue;
        }
        $fields = array(
            'module' => $moduleId,
            'resource' => $pluginId,
            'type' => 30
        );
        $modx->db->insert($fields, $MD);
        $fields = array('moduleguid' => $moduleGuid);
        $modx->db->update($fields, $P, 'id = "' . $pluginId . '"');
    }
    //удаляем плагин
    $pluginId  = $modx->db->getValue($modx->db->select('id', $P, 'name="filter_install"'));
    if (!empty($pluginId)) {
       $modx->db->delete($P, "id = $pluginId");
       $modx->db->delete($modx->getFullTableName("site_plugin_events"), "pluginid=$pluginId");
    };
}
