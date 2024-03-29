Ext.define('GibsonOS.module.hc.ir.store.Key', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIrKeyStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.ir.model.Key',
    pageSize: 100,
    constructor(data) {
        const me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/ir/keys',
            method: 'GET',
            extraParams: {
                moduleId: data.moduleId
            }
        };

        me.callParent(arguments);

        return me;
    }
});