Ext.define('GibsonOS.module.hc.ir.store.RemoteKey', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIrRemoteStore'],
    autoLoad: false,
    model: 'GibsonOS.module.hc.ir.model.remote.Button',
    remoteId: null,
    constructor(data) {
        const me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/ir/remote',
            method: 'GET',
            extraParams: {
                id: data.remoteId
            },
            reader: {
                root: 'data.buttons'
            }
        };

        me.callParent(arguments);

        return me;
    }
});