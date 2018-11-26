<template>
    <b-nav-item-dropdown v-if="unreadNotifications"
                         :text='dropdownText'>
        <b-dropdown-item v-for="(notification, index) in unreadNotifications"
                         v-bind:key="notification.id"
                         :href="notification.data.link">
            <i v-bind:class="'fa-'+notification.data.type" class="fa fa-fw"></i> {{ notification.data.title }}
        </b-dropdown-item>
        <b-dropdown-divider></b-dropdown-divider>
        <b-dropdown-item @click.prevent="markAsRead()">
            <span class="text-info"><i class="fa fa-bell-off fa-fw mr-2"></i> <strong>Mark All as Read</strong></span>
        </b-dropdown-item>
    </b-nav-item-dropdown>
</template>
<script>
  export default {
    name: 'NotificationsDropdown',
    components: {
    },
    props: {},
    data() {
      return {
        isProcessing: false
      }
    },
    computed: {
      unreadNotifications: function () {
        if (!this.$auth.check) {
          return null;
        }
        let user = this.$auth.user();
        if (user && 'unreadNotifications' in user && user.unreadNotifications.length > 0) {
          return user.unreadNotifications;
        }
        return null;
      },

      dropdownText: function () {
        if (this.unreadNotifications && this.unreadNotifications.length) {
          return '<i class="fa fa-bell fa-fw mr-2"></i> <span class="badge badge-primary">' +
            this.unreadNotifications.length + '</span>';
        }
      }
    },
    methods: {
      markAsRead: function () {
        if (!this.$auth.check()) {
          return
        }
        this.isProcessing = true
        Vue.axios.get(Vue.axios.defaults.baseURL + '/notifications/markAsRead')
          .then((response) => {
          if (response.data.error) {
            this.apiError = response.data.error
            console.log(this.apiError)
            this.isProcessing = false
          } else {
            this.isProcessing = false
            this.$emit('notificationsUpdated');
          }
        })
        .catch(response => {
          this.serverError = response;
          this.isProcessing = false
        })
      }
    }
  }
</script>
