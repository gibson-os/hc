Ext.define('GibsonOS.module.hc.ssd1306.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcSsd1306View'],
    requiredPermission: {
        module: 'hc',
        task: 'ssd1306'
    },
    cls: 'hcSsd1306View',
    data: [],
    itemSelector: 'div.hcSsd1306Pixel',
    initComponent() {
        const me = this;
        let template = [];

        for (let page = 0; page < 8; page++) {
            template.push('<div class="hcSsd1306Page">');

            for (let column = 0; column < 127; column++) {
                template.push('<div class="hcSsd1306Column">');

                for (let bit = 0; bit < 8; bit++) {
                    template.push('<div class="hcSsd1306Pixel"></div>');
                }

                template.push('</div>');
            }

            template.push('</div>');
        }

        me.store = new GibsonOS.module.hc.ssd1306.store.View();
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<tpl if="bit == 0">',
                    '<tpl if="column == 0">',
                        '<div class="hcSsd1306Page">',
                    '</tpl>',
                    '<div class="hcSsd1306Column">',
                '</tpl>',
                '<div class="hcSsd1306Pixel<tpl if="on"> on</tpl>"></div>',
                '<tpl if="bit == 7">',
                    '</div>',
                    '<tpl if="column == 127 && bit == 7"></div></tpl>',
                '</tpl>',
            '</tpl>'
        );

        me.callParent();

        let lastEntered = null;
        let setOn = true;

        const getId = (record) => {
            return record.get('page') + '' + record.get('column') + '' + record.get('bit');
        }

        me.on('itemmousedown', function (view, record, item, index, event) {
            lastEntered = getId(record);
            setOn = event.button === 0;
        });
        me.on('itemcontextmenu', function (view, record, item, index, event) {
            event.stopEvent();
        });
        me.on('itemmouseup', function (view) {
            lastEntered = null;

            GibsonOS.Ajax.request({
                url: baseDir + 'hc/ssd1306/change',
                params:  {
                    // moduleId: me.gos.data.module.id
                },
                success: function(response) {
                    view.getStore().commitChanges();
                }
            });

            console.log(view.getStore().getModifiedRecords());
        });
        me.on('itemmouseenter', function (view, record) {
            if (lastEntered === null) {
                return;
            }

            const id = getId(record);

            if (lastEntered === id) {
                return;
            }

            record.set('on', setOn);
            lastEntered = id;
        });
    }
});