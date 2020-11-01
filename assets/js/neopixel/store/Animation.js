Ext.define('GibsonOS.module.hc.neopixel.store.Animation', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.hcNeopixelAnimationStore'],
    model: 'GibsonOS.module.hc.neopixel.model.Animation',
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixelAnimation/index',
        };

        me.callParent(arguments);

        return me;
    }
});