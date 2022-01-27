Ext.define('GibsonOS.module.hc.ir.key.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcIrKeyForm'],
    requiredPermission: {
        action: 'addKey',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.READ
    },
    lastLogId: null,
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            name: 'name',
            fieldLabel: 'Name'
        },{
            xtype: 'gosCoreComponentFormFieldDisplay',
            fieldLabel: 'Protokoll'
        },{
            xtype: 'gosCoreComponentFormFieldDisplay',
            name: 'address',
            fieldLabel: 'Adresse'
        },{
            xtype: 'gosCoreComponentFormFieldDisplay',
            name: 'command',
            fieldLabel: 'Kommando'
        },{
            xtype: 'gosFormHidden',
            name: 'protocol',
        }];

        me.buttons = [{
            xtype: 'gosButton',
            itemId: 'saveButton',
            text: 'Speichern',
            handler: function() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/ir/addKey',
                    success() {
                        me.reset();
                    }
                });
            }
        },{
            xtype: 'gosButton',
            itemId: 'resetButton',
            text: 'Verwerfen',
            handler() {
                me.reset();
            }
        }];

        me.callParent();

        me.on('afterrender', function() {
            me.loadMask = new Ext.LoadMask(me, {
                msg: 'Warte auf Eingabe',
                formBind: true
            });
            me.reset();
        });
    },
    reset() {
        const me = this;

        me.getForm().reset();
        me.loadMask.show();
        me.waitForKey();
    },
    waitForKey() {
        const me = this;

        GibsonOS.Ajax.request({
            url: baseDir + 'hc/ir/waitForKey',
            params: {
                id: me.moduleId,
                lastLogId: me.lastLogId
            },
            success(response) {
                const data = Ext.decode(response.responseText).data;

                if (data) {
                    me.lastLogId = data.lastLogId;
                    me.getForm().setValues(data);
                    me.loadMask.hide();
                } else {
                    setTimeout(me.waitForKey, 1000);
                }
            },
            failure() {
                setTimeout(me.waitForKey, 1000);
            }
        });
    }
});