Ext.define('GibsonOS.module.hc.index.store.Type', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIndexTypeStore'],
    autoLoad: true,
    pageSize: 100,
    proxy: {
        type: 'gosDataProxyAjax',
        url: baseDir + 'hc/type',
        method: 'GET'
    },
    model: 'GibsonOS.module.hc.index.model.Type'
});