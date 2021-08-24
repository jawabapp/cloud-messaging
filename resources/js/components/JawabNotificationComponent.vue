<template>
  <div class="row">
    <div class="col-md-8">
      <div class="card-body">
        <div class="form-group">
          <label for="title" class="col-form-label text-md-right">{{ titleLabel }}</label>
          <input id="title" type="text" class="form-control" :class="{ 'is-invalid' : errorTitle }" name="title" v-model="titleModel" :placeholder="titlePlaceholder" >
          <jawab-character-counter :text="titleModel" :limit="140" ></jawab-character-counter>
          <span v-if="errorTitle" class="invalid-feedback" role="alert"><strong>{{ errorTitle }}</strong></span>
        </div>

        <div class="form-group">
          <label for="text" class="col-form-label text-md-right">{{ textLabel }}</label>
          <textarea id="text" class="form-control" :class="{ 'is-invalid' : errorText }" rows="4" cols="50" name="text" v-model="textModel" :placeholder="textPlaceholder" ></textarea>
          <jawab-character-counter :text="textModel" :limit="240" ></jawab-character-counter>
          <span v-if="errorText" class="invalid-feedback" role="alert"><strong>{{ errorText }}</strong></span>
        </div>

        <div class="form-group">
          <label class="col-form-label text-md-right">Notification image (optional)</label>
          <div class="custom-file">
            <input type="file" class="custom-file-input" :class="{ 'is-invalid' : errorImage }" id="image" name="image" @change="upload">
            <label class="custom-file-label" for="image">Choose file</label>
            <span v-if="errorImage" class="invalid-feedback" role="alert"><strong>{{ errorImage }}</strong></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card" style="background-color: #f5f5f5;">
        <div class="card-body">
          <div class="notification-phone-preview">
            <div class="android-preview">
              <div class="preview-background android" style="background-image: url('https://www.gstatic.com/mobilesdk/190403_mobilesdk/android.png');">
                <div class="banner-card mat-card">
                  <div class="banner-content">
                    <div class="banner-text">
                      <div class="title">{{titleModel || (textModel ? '' : titleLabel)}}</div>
                      <div class="text">{{textModel || (titleModel ? '' : textLabel)}}</div>
                    </div>
                    <img class="banner-image ng-star-inserted" :src="image">
                  </div>
                </div>
              </div>
              <h5 class="phone-label">Android</h5>
            </div>
            <div>
              <div class="preview-background ios" style="background-image: url('https://www.gstatic.com/mobilesdk/190403_mobilesdk/iphone.png');">
                <div class="banner-card mat-card">
                  <div class="banner-content">
                    <div class="banner-text">
                      <div class="title">{{titleModel || (textModel ? '' : titleLabel)}}</div>
                      <div class="text">{{textModel || (titleModel ? '' : textLabel)}}</div>
                    </div>
                    <img class="banner-image ng-star-inserted" :src="image">
                  </div>
                </div>
              </div>
              <h5 class="phone-label">iOS</h5>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import JawabCharacterCounterComponent from "./JawabCharacterCounterComponent";
export default {
  name:"JawabNotificationComponent",
  components:{
    'jawab-character-counter': JawabCharacterCounterComponent
  },
  props: {
    title: {
      type: String,
      required: false,
      default: ''
    },
    text: {
      type: String,
      required: false,
      default: ''
    },
    errorTitle: {
      type: String,
      required: false,
      default: ''
    },
    errorText: {
      type: String,
      required: false,
      default: ''
    },
    errorImage: {
      type: String,
      required: false,
      default: ''
    }
  },
  data() {
    return {
      titleLabel: 'Notification title',
      textLabel: 'Notification text',
      titlePlaceholder: 'Enter optional title',
      textPlaceholder: 'Enter notification text ',
      titleModel: '',
      textModel: '',
      image: 'https://www.gstatic.com/mobilesdk/180130_mobilesdk/images/image_placeholder.png'
    }
  },
  methods: {
    upload(e) {
      const image = e.target.files[0];
      const reader = new FileReader();
      reader.readAsDataURL(image);
      reader.onload = e => {
        this.image = e.target.result;
      };

      e.target.nextSibling.nextSibling.innerHTML = image.name
    }
  },
  created() {
    this.nameModel = this.name;
    this.titleModel = this.title;
    this.textModel = this.text;
  }
}
</script>

<style scoped>
.notification-phone-preview {
  text-align: center;
}

.preview-background {
  background-repeat: no-repeat;
  background-size: 100%;
  display: flex;
  margin: 4px;
}

.mat-card.mat-card:not([class*="mat-elevation-z"]) {
  -moz-box-shadow: 0 1px 2px 0 rgba(60,64,67,.3),0 1px 3px 1px rgba(60,64,67,.15);
  box-shadow: 0 1px 2px 0
  rgba(60,64,67,.3),0 1px 3px 1px
  rgba(60,64,67,.15);
}

.mat-card.banner-card {
  overflow: hidden;
  width: 100%;
}

.mat-card {
  transition: box-shadow 280ms cubic-bezier(0.4, 0, 0.2, 1);
  display: block;
  position: relative;
  padding: 16px;
  border-radius: 4px;
}

.mat-card {
  background:
      white;
  color:
      rgba(0,0,0,0.87);
}

.mat-button-toggle, .mat-card {
  font-family: Roboto,"Helvetica Neue",sans-serif;
}

.mat-card > :last-child:not(.mat-card-footer), .mat-card-content > :last-child:not(.mat-card-footer) {
  margin-bottom: 0;
}

.banner-content {
  display: flex;
  justify-content: space-between;
}

.mat-card > :first-child, .mat-card-content > :first-child {
  margin-top: 0;
}

.mat-card {
  color:
      rgba(0,0,0,0.87);
}

.banner-text {
  font-size: 10px;
  min-height: 28px;
  text-align: left;
  overflow: hidden;
}

.title {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: pre;
}

.text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: pre;
}

.banner-card {
  border-radius: 0px;
  margin: 4px;
  padding: 8px;
}

.banner-image {
  height: 40px;
  margin-left: 8px;
}

.android-preview {
  margin: 24px 0;
}

.android.preview-background {
  padding: 15% 4.6632124352% 0;
}

.phone-label {
  color:
      rgba(0,0,0,.4);
  font-size: 13px;
  margin-top: 24px;
}

h5 {
  margin-block-start: 0;
  margin-block-end: 0;
}

.ios.preview-background {
  padding: 14% 6.2176165803% 0;
}

.ios .mat-card {
  border-radius: 8px;
}
</style>
