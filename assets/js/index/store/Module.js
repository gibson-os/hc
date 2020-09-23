Ext.define('GibsonOS.module.hc.index.store.Module', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIndexModuleStore'],
    autoLoad: true,
    pageSize: 100,
    proxy: {
        type: 'gosDataProxyAjax',
        url: baseDir + 'hc/module/index'
    },
    model: 'GibsonOS.module.hc.index.model.Module'
});