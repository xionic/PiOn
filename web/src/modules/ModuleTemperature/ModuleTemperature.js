
import ModuleText from '../ModuleText/ModuleText.js';


class ModuleTemperature extends ModuleText {
    
    updatePostfix(){
        let date = new Date(this.props.value.timestamp * 1000);

        let ts = date.getTime();
        let cur_ts = (new Date).getTime();
        let num_days = Math.floor((cur_ts - ts) / 86400000);
        let day_str = ""
        if (num_days > 0){
            day_str = " -" + num_days + " days";
        }

        let time = ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2) + ":" + ('0' + date.getSeconds()).slice(-2);

        this.text_postfix = "°@" + time + day_str;   
    }
}

export default ModuleTemperature;
/*

*/