<template>
  <main v-if="$auth.ready()"
        role="main"
        class="recipe-print-container">
    <div v-if="apiError" show variant="danger">
      API Error: {{ apiError.message }}
    </div>
    <div v-if="serverError" show variant="danger">
      Server Error: {{ serverError }}
    </div>

    <div v-if="isLoaded">
      <div v-if="recipes.length > 1"
           class="no-print">
        <div class="form-group row">
          <label for="batchAmountInputId" class="col-sm-2 col-form-label">Set Amount:</label>
          <div class="col-sm-10">
            <input type="number"
                   inputmode="numeric"
                   size="4"
                   maxlength="10"
                   placeholder="0.0"
                   id="batchAmountInputId"
                   class="form-control form-control-sm"
                   @input="globalBatchAmountInput"
                   v-model.number="globalBatchAmount">
          </div>
        </div>
      </div>

      <div v-for="recipe in recipes"
           v-bind:class="isSimple ? 'recipe-print-multiple' : 'recipe-print-item'">
        <div class="d-flex justify-content-between">
          <div class="flex-grow-1">
            <h1 class="mb-2">
              {{ recipe.name }}
            </h1>
            <span v-if="recipe.otherNames">
              Other Names: {{ recipe.otherNames }}
            </span>
            <h4 class="mt-2">{{ getLink(recipe) }}</h4>
          </div>
          <div>
            <h1 v-html="'&#9651;' + glazyHelper.getConeString(recipe)">
            </h1>
          </div>
        </div>
        <div class="row">
          <div class="col-3 col-lg-4">
            <div v-if="recipe.parentId && recipe.parentName">
                Parent Material: <strong>{{ recipe.parentName }}</strong>
            </div>
            <div>
              Atmospheres: <strong>{{ glazyHelper.getAtmospheresLongString(recipe) }}</strong>
            </div>
            <div>
              Type: <strong>{{ glazyHelper.getMaterialTypeString(recipe) }}</strong>
            </div>
            <div v-if="recipe.materialStateId">
              Status:
              <strong v-if="recipe.materialStateId === 1">Test</strong>
              <strong v-else-if="recipe.materialStateId === 3">Discontinued</strong>
              <strong v-else>Production</strong>
            </div>
            <div v-if="recipe.surfaceTypeName">
              Surface: <strong>{{ recipe.surfaceTypeName }}</strong>
            </div>
            <div v-if="recipe.transparencyTypeName">
              Transparency: <strong>{{ recipe.transparencyTypeName }}</strong>
            </div>
            <div v-if="recipe.countryName">
              Country: <strong>{{ recipe.countryName }}</strong>
            </div>
            <div class="mt-3">
              User: <strong>{{ recipe.createdByUser.name }}</strong>
            </div>
            <div>
              Created:
              {{ glazyHelper.getFormattedDate(recipe.createdAt) }}
            </div>
            <div>
              Updated:
              {{ glazyHelper.getFormattedDate(recipe.updatedAt) }}
            </div>
            <div class="mt-3">
              Current User:
              <br/>
              <strong>{{ $auth.user().name }}</strong>
              <br/>
              Current Date:
              <br/>
              {{ glazyHelper.getFormattedNow() }}
            </div>
          </div>
          <div class="col-9 col-lg-8">
            <div v-if="!recipe.isPrimitive">
              <material-recipe-calculator
                  :material="recipe"
                  :initialBatchSize="globalBatchAmount"
                  :isPrint="true"
                  v-on:batchInput="batchInput">
              </material-recipe-calculator>
            </div>
          </div>
        </div>

        <div v-if="!isSimple">
          <div v-if="!recipe.isPrimitive && recipe.baseTypeId == glazeTypeId">
            <h3 class="mt-4">
              UMF Analysis
            </h3>

            <div class="d-flex justify-content-between">
              <div>
                <umf-traditional-notation
                    :material="recipe.material"
                    :showOxideList="false"
                    :isPrint="true"
                    :squareSize="100">
                </umf-traditional-notation>
              </div>
              <div>
                <h6 class="">R<sub>2</sub>O : RO</h6>
                <h2>
                  {{ Number(recipe.analysis.umfAnalysis.R2OTotal).toFixed(2) }}
                  :
                  {{ Number(recipe.analysis.umfAnalysis.ROTotal).toFixed(2) }}
                </h2>
              </div>
              <div>
                <h6 class="card-title">SiO<sub>2</sub> : Al<sub>2</sub>O<sub>3</sub></h6>
                <h2>
                  {{ Number(recipe.analysis.umfAnalysis.SiO2Al2O3Ratio).toFixed(2) }}
                </h2>
              </div>
            </div>
          </div>

          <div class="row" v-if="recipe.isPrimitive">
            <div class="col-12 col-sm-12 col-md-auto mb-2">
              Weight:
              <span class="badge badge-info">
                    {{ Number(recipe.analysis.weight).toFixed(3) }}
                  </span>&nbsp;&nbsp;
              Calculated Oxide Weight:
              <span class="badge badge-info">
                    {{ Number(recipe.analysis.oxideWeight).toFixed(3) }}
                  </span>&nbsp;&nbsp;
              <span v-if="'percentageAnalysis' in recipe.analysis">
                    LOI:
                    <span class="badge badge-info">
                      {{ Number(recipe.analysis.percentageAnalysis.loi).toFixed(3) }}
                    </span>
                  </span>
            </div>
          </div>

          <div v-if="recipe.isPrimitive || recipe.isAnalysis" class="row">
            <div class="col-md-12">
              <simple-analysis-table :material="recipe.material"></simple-analysis-table>
            </div>
          </div>

          <h3 class="mt-4">
            % Analysis
          </h3>
          <component-table
              :material="recipe.material"
              :isFormula="false"
              :isPrint="true">
          </component-table>

          <div v-if="recipe.description">
            <h3>Notes</h3>
            {{ recipe.description }}
          </div>
        </div>
        <hr class="mb-4 no-print"/>
      </div>

      <div v-if="recipes.length > 1"
           class="batch-report">
        <h1>Batch Report</h1>
        <table class="table batch-report-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th class="text-right">Batch Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="recipe in recipes">
              <td>{{ recipe.id }}</td>
              <td>{{ recipe.name }}</td>
              <td class="amount">{{ recipe.batchSize.toFixed(2) }}</td>
            </tr>
            <tr>
              <td></td>
              <td>Total (Excluding additional materials)</td>
              <td class="amount"><strong>{{ this.batchTotal.toFixed(2) }}</strong></td>
            </tr>
          </tbody>
        </table>

        <table class="table batch-report-table">
          <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th class="text-right">Amount</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="batch in batchComponentMaterials">
            <td>{{ batch.id }}</td>
            <td>{{ batch.name }}</td>
            <td class="amount">{{ batch.amount.toFixed(2) }}</td>
          </tr>
          <tr>
            <td></td>
            <td>Total</td>
            <td class="amount"><strong>{{ this.componentTotal.toFixed(2) }}</strong></td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>

</template>

<script>

  import Material from 'ceramicscalc-js/src/material/Material'

  import MaterialTypes from 'ceramicscalc-js/src/material/MaterialTypes'

  import MaterialRecipeCalculator from '../components/glazy/recipe/MaterialRecipeCalculator.vue'

  import UmfTraditionalNotation from '../components/glazy/analysis/UmfTraditionalNotation.vue';
  import ComponentTable from '../components/glazy/analysis/ComponentTable.vue'
  import SimpleAnalysisTable from '../components/glazy/analysis/SimpleAnalysisTable.vue'

  import GlazyHelper from '../components/glazy/helpers/glazy-helper'

  import Vue from 'vue'

  export default {
    name: 'Recipe',
    metaInfo () {
      return {
        title: this.meta.title,
        meta: [
          {
            'vmid': "description",
            'property': 'description',
            'content': this.meta.description
          },
          {
            'property': 'og:description',
            'content': this.meta.description
          },
          {
            'property': 'og:title',
            'content': this.meta.title
          },
          {
            'property': 'og:url',
            'content': this.meta.url
          },
          {
            'property': 'twitter:description',
            'content': this.meta.description
          }
        ]
      }
    },
    components: {
      MaterialRecipeCalculator,
      UmfTraditionalNotation,
      SimpleAnalysisTable,
      ComponentTable
    },
    props: {
      recipe_id: {
        type: Number,
        default: null
      },
      current_user: {
        type: Object,
        default: null
      }
    },

    data() {
      return {
        batchComponentMaterials: [],
        globalBatchAmount: '',
        batchTotal: 0,
        componentTotal: 0,
        isSimple: this.$route.query.simple,
        materialIds: this.$route.query.id,
        recipes: null,
        materials: null,
        glazeTypeId: MaterialTypes.GLAZE_TYPE_ID,
        glazyHelper: new GlazyHelper(),
        isProcessing: false,
        apiError: null,
        serverError: null
      }
    },

    mounted() {
      if (this.$route.query.amount) {
        this.globalBatchAmount = this.$route.query.amount;
      }
      this.fetchRecipe();
    },
    computed : {
      isLoaded: function() {
        if (this.recipes && this.recipes.length) {
          return true;
        }
        return false;
      },
      meta: function () {
        var meta = {
          title: 'Print Recipes',
          description: 'Glazy Ceramics Recipes',
          url: GLAZY_APP_URL + this.$route.fullPath,
        };
        return meta
      }
    },
    watch: {
    },
    methods : {

      fetchRecipe: function (){
        if (this.materialIds) {
          let url = '/recipes/calculatorSearch?';
          if (Array.isArray(this.materialIds) && this.materialIds.length > 0) {
            this.materialIds.forEach((materialId) => {
              url += 'id[]=' + materialId + '&';
            });
          }
          else {
            url += 'id=' + this.materialIds;
          }
          this.sendRecipeGetRequest(url);
        }
      },

      sendRecipeGetRequest: function (url) {
        this.apiError = null
        this.isProcessing = true
        Vue.axios.get(Vue.axios.defaults.baseURL + url)
          .then((response) => {
          this.isProcessing = false
          if (response.data.error) {
            this.apiError = response.data.error
            console.log(this.apiError)
          } else {
            this.recipes = null
            this.recipes = response.data.data
            if (this.recipes && this.recipes.length) {
              this.recipes.forEach((recipe) => {
                if (recipe.isPrimitive || recipe.isAnalysis) {
                  // Don't calculate formula from percentage
                  recipe.material = Material.createFromJson(recipe, false)
                }
                else {
                  recipe.material = Material.createFromJson(recipe, true)
                }
                Vue.set(recipe, 'batchSize', 0);
                if ('materialComponents' in recipe && recipe.materialComponents.length > 0) {
                  let totalBaseAmount = 0;
                  recipe.materialComponents.forEach((component) => {
                    if (!component.isAdditional) {
                      totalBaseAmount += parseFloat(component.percentageAmount);
                    }
                  });
                  Vue.set(recipe, 'totalBaseAmount', totalBaseAmount);
                  recipe.materialComponents.forEach((component) => {
                    let material = this.batchComponentMaterials.find(material => material.id == component.material.id);
                    let amount = parseFloat(component.percentageAmount) * parseFloat(recipe.batchSize) / recipe.totalBaseAmount;
                    this.componentTotal += amount;
                    if (material) {
                      Vue.set(material, 'amount', amount);
                    }
                    else {
                      this.batchComponentMaterials.push({
                        id: component.material.id,
                        name: component.material.name,
                        amount: amount
                      });
                    }
                  });
                }
              });
            }
          }
        })
        .catch(response => {
          this.recipes = null;
          if (response.response && response.response.status) {
            if (response.response.status === 401) {
              this.$auth.refresh() // attempt refresh
            } else {
              this.serverError = response.response.message;
            }
          }
          this.isProcessing = false
        })
      },

      batchInput: function (event) {
        if (this.recipes) {
          let recipe = this.recipes.find(recipe => recipe.id == event.materialId);
          if (recipe) {
            Vue.set(recipe, 'batchSize', event.batchSize);
          }
        }
        this.recalculateComponentMaterials();
      },

      globalBatchAmountInput: function(val) {
        if (this.recipes && this.recipes.length) {
          this.recipes.forEach((recipe) => {
            Vue.set(recipe, 'batchSize', this.globalBatchAmount);
          });
          this.recalculateComponentMaterials();
        }
      },

      recalculateComponentMaterials: function() {
        this.batchTotal = 0;
        this.componentTotal = 0;
        this.batchComponentMaterials.forEach((material) => {
          Vue.set(material, 'amount', 0);
        });
        this.recipes.forEach((recipe) => {
          this.batchTotal += recipe.batchSize;
          if ('materialComponents' in recipe && recipe.materialComponents.length > 0) {
            recipe.materialComponents.forEach((component) => {
              let material = this.batchComponentMaterials.find(material => material.id == component.material.id);
              let amount = parseFloat(component.percentageAmount) * parseFloat(recipe.batchSize) / recipe.totalBaseAmount;
              this.componentTotal += amount;
              Vue.set(material, 'amount', material.amount + amount);
            });
          }
        });
        this.batchComponentMaterials.sort(function(a, b) {
          return b.amount - a.amount;
        });
      },

      getLink: function (material) {
        let link = 'https://glazy.org/';
        if (material) {
          if (material.isPrimitive) {
            link += 'materials';
          }
          else if (material.isAnalysis) {
            link += 'analyses';
          }
          else {
            link += 'recipes';
          }
          link += '/' + material.id;
        }
        return link;
      }
    }
  }
</script>


<style scoped>

  .recipe-print-container {
    min-width: 7in !important;
    background-color: #ffffff;
    margin-top: -50px;
    padding: 20px;
  }

  .recipe-print-item {
    page-break-after: always;
  }

  .recipe-print-multiple:nth-child(even){
    page-break-after: always;
  }

  .batch-report {
    page-break-before: always;
  }

  .batch-report-table tbody tr td {
    padding: 0;
  }

  .batch-report-table tbody tr td.amount {
    font-family: monospace;
    text-align: right !important;
  }

  @media print {
    body {
      margin-top: 10mm;
      margin-bottom: 5mm;
      margin-left: 5mm;
      margin-right: 5mm;
      min-width: 7in;
    }
    .no-print {
      display: none;
    }
    .material-recipe-calculator-table tbody tr td {
      font-size: 1.2rem;
      line-height: 1.8rem;
    }
  }

  @page {
    margin: 0.5cm;
  }

</style>