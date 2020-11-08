Ext.define('GibsonOS.module.hc.neopixel.store.Image', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcNeopixelImageStore'],
    autoLoad: false,
    model: 'GibsonOS.module.hc.neopixel.model.Image',
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixel/images'
        };

        me.callParent(arguments);

        return me;
    }
});