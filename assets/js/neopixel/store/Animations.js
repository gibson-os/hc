Ext.define('GibsonOS.module.hc.neopixel.store.Animations', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.hcNeopixelAnimationsStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.neopixel.model.Animations',
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixelAnimation/list',
            extraParams: {
                moduleId: data.gos.data.module.id
            },
            success: function(response) {
                console.log(Ext.decode(response.responseText));
            }
        };

        me.callParent(arguments);

        return me;
    }
});