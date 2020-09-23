Ext.define('GibsonOS.module.hc.index.store.Master', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIndexMasterStore'],
    autoLoad: true,
    pageSize: 100,
    proxy: {
        type: 'gosDataProxyAjax',
        url: baseDir + 'hc/master/index'
    },
    model: 'GibsonOS.module.hc.index.model.Master'
});