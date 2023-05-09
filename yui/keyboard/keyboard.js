/**
 * YUI module for adding left/right keyboard shortcuts for navigating
 * between chats.
 *
 * @author  Dan Poltawski <dan@moodle.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
YUI.add('moodle-local_chatlogs-keyboard', function(Y) {

    var KEYBOARD = function() {
        KEYBOARD.superclass.constructor.apply(this, arguments);
    }

    Y.extend(KEYBOARD, Y.Base, {

        initializer : function(config) {
            Y.on('keyup', this.handle_key);
        },

        handle_key : function (e) {
            if (e.keyCode == 37) {
                // left
                var previouslink = Y.one('.previouslink');
                if (previouslink) {
                    location.href = previouslink.get('href');
                }
            }else if (e.keyCode==39){
                var nextlink = Y.one('.nextlink');
                if (nextlink) {
                    location.href = nextlink.get('href');
                }
            }
        },

    }, {
        NAME : 'keyboard',
        ATTRS : { }
    });

    M.local_chatlogs = M.local_chatlogs || {};

    M.local_chatlogs.init_keyboard = function(config) {
        M.local_chatlogs.KEYBOARD = new KEYBOARD(config);
    }

}, '@VERSION@', { requires:['base', 'event', 'event-simulate', 'event-key'] });
