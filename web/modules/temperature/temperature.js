import {html,css}  from 'lit-element';
import {pion_base} from '../pion_base.js';
import {register_module} from '../../main.js';
import {module_text} from '../text/text.js';


export class module_temperature extends module_text {
	
	constructor() {
		super();
    }

	render() {
		let time = '';
		if(this.val.hasOwnProperty("timestamp")){
			let date = new Date(this.val.timestamp * 1000);
			time = ('0' + date.getHours()).slice(-2) + ":" + ('0' +date.getMinutes()).slice(-2) + ":" + ('0' + date.getSeconds()).slice(-2);
		}
		return html`<span>${this.val.data}&deg;C@${time}</span>`;
	}	
}

customElements.define('module-temperature', module_temperature);

$().ready(function(){
	register_module({
		name:"temperature"
	});
});

