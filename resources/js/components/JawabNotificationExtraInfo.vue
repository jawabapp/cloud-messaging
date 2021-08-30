<template>
  <div class="row">
    <div class="col-md-8">
      <div class="card-body">
        <div class="form-group">
          <label for="name" class="col-form-label text-md-right">{{ info.nameLabel }}</label>
          <input id="name" type="text" class="form-control" :class="{ 'is-invalid' : errors.name && errors.name.length }" name="extra_info[name]" v-model="info.nameModel"/>
          <jawab-character-counter :text="info.nameModel" :limit="140" ></jawab-character-counter>
          <template v-if="errorExtraInfo">
            <span v-for="(error, index) in errors.name" :key="index" class="invalid-feedback" role="alert"><strong>{{ error }}</strong></span>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import JawabCharacterCounterComponent from "./JawabCharacterCounterComponent";
export default {
  name:"JawabNotificationExtraInfo",
  components:{
    'jawab-character-counter': JawabCharacterCounterComponent
  },
  props: {
    extraInfo:{
      type: String,
      required: false,
      default: ''
    },
    errorExtraInfo: {
      type: String,
      required: false,
      default: ''
    },
  },
  data() {
    return {
      info:{
        nameLabel: 'Notification Name',
        nameModel: '',
      },
      errors:{
        name:[]
      }
    }
  },
  created() {
    if (this.extraInfo) {
      let extraInfo = JSON.parse(this.extraInfo);
      if (typeof extraInfo === 'object' && extraInfo !== null) {
        this.info.nameModel = extraInfo.name;
      }else{
        this.info.nameModel = this.extraInfo.name;
      }
    }

    if (this.errorExtraInfo) {
      let errorExtraInfo = JSON.parse(this.errorExtraInfo);
      if (typeof errorExtraInfo === 'object' && errorExtraInfo !== null) {
        this.errors.name = errorExtraInfo['extra_info.name'];
      }
    }
  }
}
</script>
