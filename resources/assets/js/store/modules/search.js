import SearchQuery from '../../components/glazy/search/search-query'

const state = {
  query: null,
  querystring: null,
  isPrimitive: false,
  isAnalysis: false,
  searchItems: [],
  searchPagination: null,
  searchUser: null,
  isProcessing: false,
  serverError: null,
  apiError: null
}

const getters = {
  query(state) {
    return state.query
  },
  querystring(state) {
    return state.querystring
  },
  isPrimitive(state) {
    return state.isPrimitive
  },
  isAnalysis(state) {
    return state.isAnalysis
  },
  searchItems(state) {
    return state.searchItems
  },
  searchPagination(state) {
    return state.searchPagination
  },
  searchUser(state) {
    return state.searchUser
  },
  isProcessing(state) {
    return state.isProcessing
  }
}

const mutations = {
  setQuery(state, payload) {
    state.query = payload
  },
  setQuerystring(state, payload) {
    state.querystring = payload
  },
  setIsPrimitive(state, payload) {
    state.isPrimitive = payload
  },
  setIsAnalysis(state, payload) {
    state.isAnalysis = payload
  },
  setSearchItems(state, payload) {
    state.searchItems = payload
  },
  setSearchPagination(state, payload) {
    state.searchPagination = payload
  },
  setSearchUser(state, payload) {
    state.searchUser = payload
  },
  setServerError(state, payload) {
    state.serverError = payload
  },
  setApiError(state, payload) {
    state.apiError = payload
  },
  isProcessing(state) {
    state.isProcessing = true
  },
  isNotProcessing(state) {
    state.isProcessing = false
  }

}

const actions = {
  resetError (context) {
    context.commit('setServerError', null)
    context.commit('setApiError', null)
  },
  search (context, payload) {
    // Don't search if not necessary
    var oldQuerystring = context.getters.querystring;
    if (oldQuerystring) {
      var newQuery = payload.query.getMinimalQuery();
      if (payload.isPrimitive) { newQuery.primitive = 1; }
      if (payload.isAnalysis) { newQuery.analysis = 1; }
      var newQuerystring = payload.query.toQuerystring(newQuery);
      if (newQuerystring === oldQuerystring) {
        // No need to perform search
        return;
      }
    }
    context.commit('setQuery', payload.query)
    context.commit('setIsPrimitive', payload.isPrimitive)
    context.commit('setIsAnalysis', payload.isAnalysis)
    context.dispatch('refresh')
  },
  refresh (context) {
    // Let components know we are processing a request
    context.commit('isProcessing')

    context.commit('setSearchItems', [])

    // Clear out all old errors and search user
    // context.commit('setSearchUser', null)
    context.dispatch('resetError')

    var query = context.getters.query;
    var myQuery = {};
    if (query) {
      myQuery = query.getMinimalQuery();
    }

    var isPrimitive = context.getters.isPrimitive

    if (isPrimitive) {
      myQuery.primitive = 1
    }

    var isAnalysis = context.getters.isAnalysis

    if (isAnalysis) {
      myQuery.analysis = 1
    }

    const mySearchQuery = new SearchQuery();
    var querystring = mySearchQuery.toQuerystring(myQuery)
    context.commit('setQuerystring', querystring);

    Vue.axios.get(Vue.axios.defaults.baseURL + '/search?' + querystring)
      .then((response) => {
      if (response.data.error) {
        // Reset both search items & search user
        context.commit('setSearchUser', null)
        context.commit('setApiError', response.data.error)
        context.commit('isNotProcessing')
      } else {
        // Reset both search items & search user
        context.commit('setSearchUser', null)
        if (response.data.data) {
          context.commit('setSearchItems', response.data.data)
        }
        context.commit('setSearchPagination', response.data.meta.pagination)
        if ('user' in response.data.meta && 'data' in response.data.meta.user) {
          context.commit('setSearchUser', response.data.meta.user.data)
        }
        context.commit('isNotProcessing')
      }
    })
    .catch(response => {
      // Reset both search items & search user
      context.commit('setSearchUser', null)
      if (response.response && response.response.status) {
        if (response.response.status === 401) {
          this.$auth.refresh() // attempt refresh
        } else {
          context.commit('setServerError', response.response.message)
        }
      }
      context.commit('isNotProcessing')
    })
  }
}

export default {
  state,
  mutations,
  actions,
  getters,
  namespaced: true
}
