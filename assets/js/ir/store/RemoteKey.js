Ext.define('GibsonOS.module.hc.ir.store.RemoteKey', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIrRemoteStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.ir.model.RemoteKey',
    remoteId: null,
    constructor(data) {
        const me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/ir/remote',
            extraParams: {
                remoteId: data.remoteId
            },
            reader: {
                root: 'data.keys'
            }
        };

        me.callParent(arguments);

        return me;
    }
});