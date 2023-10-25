Ext.define('GibsonOS.module.hc.blueprint.store.Blueprint', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.hcBlueprintBlueprintStore'],
    autoLoad: true,
    pageSize: 100,
    proxy: {
        type: 'gosDataProxyAjax',
        url: baseDir + 'hc/blueprint/index',
        method: 'GET',
    },
    model: 'GibsonOS.module.hc.blueprint.model.Blueprint'
});