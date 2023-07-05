Ext.define('GibsonOS.module.hc.ir.store.Remote', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIrRemoteStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.ir.model.Remote',
    constructor(data) {
        const me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/ir/remotes',
            method: 'GET',
            extraParams: {
                moduleId: data.moduleId
            }
        };

        me.callParent(arguments);

        return me;
    }
});