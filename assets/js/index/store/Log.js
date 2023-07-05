Ext.define('GibsonOS.module.hc.index.store.Log', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIndexLogStore'],
    autoLoad: true,
    pageSize: 100,
    proxy: {
        type: 'gosDataProxyAjax',
        url: baseDir + 'hc/index/log',
        method: 'GET',
    },
    model: 'GibsonOS.module.hc.index.model.Log'
});