<template>

    <div class="row content-row">
        <div class="col-md-4 offset-md-4 col-sm-12">
            <div class="card glazy-login-card">
                <div class="card-body">
                    <h4 class="card-title">Login to Glazy</h4>

                    <b-alert v-if="alertMessage" show variant="success">
                        {{ alertMessage }}
                    </b-alert>
                    <b-alert v-if="apiError" show variant="danger">
                        API Error: {{ apiError.message }}
                    </b-alert>
                    <b-alert v-if="serverError" show variant="danger">
                        {{ serverError }}
                    </b-alert>
                    <b-alert v-if="firstLogin" show variant="info">
                        Registration successful!  Please login below.
                    </b-alert>
                    <div class="load-container load7 fullscreen" v-if="isProcessing">
                        <div class="loader">Loading...</div>
                    </div>

                    <div v-show="!code || !type">
                        <div class="col-12">
                            <a @click="loginSocial('facebook')" href="#" class="btn btn-facebook btn-block btn-sm">
                                <i class="fa fa-facebook-square"></i> Login with Facebook
                            </a>
                            <a @click="loginSocial('google')" class="btn btn-google btn-block btn-sm">
                                <i class="fa fa-google-plus"></i> Login with Google
                            </a>
                        </div>
                        <div class="col-12">
                            <form @submit="login" @reset="onReset">
                                <div class="form-group">
                                    <label for="inputEmail">Email address</label>
                                    <input type="email"
                                           class="form-control"
                                           id="inputEmail"
                                           aria-describedby="emailHelp"
                                           placeholder="Enter email"
                                           v-model="data.body.email">
                                    <small v-if="errors && 'email' in errors"
                                            id="emailHelp"
                                           class="form-text text-muted">
                                        Please enter a valid Email address.</small>
                                </div>
                                <div class="form-group">
                                    <label for="inputPassword">Password</label>
                                    <input type="password"
                                           class="form-control"
                                           id="inputPassword"
                                           placeholder="Password"
                                           v-model="data.body.password">
                                    <small v-if="errors && 'password' in errors"
                                           id="emailHelp"
                                           class="form-text text-muted">
                                        Please enter a valid password.</small>
                                </div>
                                <button type="submit" class="btn btn-info btn-block btn-sm">Login</button>
                            </form>
                        </div>
                        <div class="col-12">
                            <a v-b-modal.forgotPasswordModal>Forgot Password?</a>
                        </div>
                    </div>

                    <div v-show="code && type">
                        Logging you in via {{ type }}...
                    </div>
                </div>
            </div>

            <b-modal id="forgotPasswordModal"
                     title="Forgot your password?"
                     v-on:ok="completeForgotPassword()"
                     ok-title="Reset My Password!"
            >
                <p>
                    Enter your email address here to have your password reset.
                </p>
                <form>
                    <div class="form-group">
                        <label for="inputForgotPasswordEmail">Email address</label>
                        <input type="email"
                               class="form-control"
                               id="inputForgotPasswordEmail"
                               aria-describedby="emailHelp"
                               placeholder="Enter email"
                               v-model="forgotPasswordEmail">
                    </div>
                </form>
            </b-modal>

        </div>
    </div>

</template>

<script>
  export default {
    name: 'Login',

    props: {
    },
    data() {
      return {
        context: 'login context',

        data: {
          body: {
            email: '',
            password: ''
          },
          rememberMe: true,
          fetchUser: true
        },
        forgotPasswordEmail: null,
        code: this.$route.query.code,
        type: this.$route.params.type,
        firstLogin: this.$route.query.firstLogin,
        apiError: null,
        serverError: null,
        alertMessage: null,
        errors: null,
        isProcessing: false
      }
    },
    computed : {
    },
    mounted () {
      if (this.code) {
        this.$auth.oauth2 ({
          code: true,
          provider: this.type,
          data: {
            code: this.$route.query.code
          },
          success: function(res) {
            // console.log('social success ' + this.context);
          },
          error: function (res) {
            // console.log('social error ' + this.context);
          }
        })
      }

      if (this.$route.query && this.$route.query.error) {
        if (Number(this.$route.query.error) === 401) {
          this.error = 'Unauthorized, please login to perform this function.'
        }
      }
      if(this.firstLogin) {
          this.cancelLogin();
      }
    },
    methods: {
      login (evt) {
        this.isProcessing = true
        evt.preventDefault();
        var redirect = this.$auth.redirect()
        this.$auth.login({
          data: this.data.body,
          rememberMe: this.data.rememberMe,
          autoLogin: true,
          redirect: {
            name: redirect ? redirect.from.name : 'search'
          },
          fetchUser: this.data.fetchUser,
          success (res) {
            this.isProcessing = false
            // console.log('success ' + this.context)
            // this.$router.push({ name: 'user', params: { id: this.$auth.user().id }})
          },
          error (res) {
            this.isProcessing = false
            // console.log('error ' + this.context)
            if (res.response.data && res.response.data.error) {
              this.errors = res.response.data.error.errors
              console.log(this.errors);
            }
            if (res.response.status &&
              Number(res.response.status) === 403 ) {
              this.serverError = 'Incorrect email & password.'
            }
          }
        })
      },

      loginSocial(type) {
        this.$auth.oauth2({
          provider: type || this.type
        })
      },

      cancelLogin() {
        this.$router.push('search')
      },

      onReset (evt) {
        evt.preventDefault();
          /* Reset our form values */
        this.data.body.email = null;
        this.data.body.password = null;
      },

      completeForgotPassword () {
        var resetForm = {
          email: this.forgotPasswordEmail
        };
        Vue.axios.post(Vue.axios.defaults.baseURL + '/auth/recovery', resetForm)
          .then((response) => {
              if (response.data.error) {
                  this.apiError = response.data.error
                  console.log(this.apiError)
                } else {
                  this.alertMessage = "Password reset and email sent!";
                  this.$emit('updatedRecipeComponents');
                }
          })
          .catch((response) => {
            this.serverError = "Sorry, that email address could not be found.";
            //this.serverError = response;
          })
      }
    }
  }

</script>

<style>

    .content-row {
        padding-top: 15px;
    }

    .glazy-login-card .card-body .card-title {
        margin-top: 0;
    }
</style>