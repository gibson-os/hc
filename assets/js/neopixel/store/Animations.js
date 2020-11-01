Ext.define('GibsonOS.module.hc.neopixel.store.Animations', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.hcNeopixelAnimationsStore'],
    model: 'GibsonOS.module.hc.neopixel.model.Animations',
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixelAnimation/list'
        };

        me.callParent(arguments);

        return me;
    }
});