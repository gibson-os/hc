Ext.define('GibsonOS.module.hc.ir.store.RemoteKey', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIrRemoteStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.ir.model.RemoteKey',
    constructor(data) {
        const me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/ir/remote',
            extraParams: {
                remoteId: data.remoteId
            }
        };

        me.callParent(arguments);

        return me;
    }
});