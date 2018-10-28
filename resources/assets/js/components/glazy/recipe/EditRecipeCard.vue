<template>
    <b-card
        v-if="isLoaded"
        @mouseover="highlightMaterial(material.id)"
        @mouseleave="unhighlightMaterial(material.id)"
        class="edit-recipe-components-card">

        <div class="d-flex mb-2">
            <div v-if="this.material.id > 0"
                 class="flex-grow-1 pr-2">
                <router-link :to="{ name: 'material', params: { id: material.id }}" class="">
                    {{ this.material.name }}
                </router-link>
            </div>
            <div v-else-if="$auth.check()"
                 class="flex-grow-1 pr-2">
                <input id="new_name_input"
                       v-model="material.name"
                       v-on:blur.native="unfocusAll"
                       @focus="unfocusAll"
                       placeholder="New Name"
                       class="form-control form-control-sm">
            </div>
            <div v-if="material.materialComponents.length && $auth.check()">
                <b-button-toolbar>
                    <b-button-group size="sm">
                        <button class="btn btn-sm btn-info btn-recipe-action"
                                @click.prevent="store">
                            <i class="fa fa-save"></i>
                            <span v-if="this.material.id > 0">Update</span>
                            <span v-else>Save</span>
                            </b-button>
                        </button>
                        <button class="btn btn-sm btn-info btn-recipe-action"
                                @click.prevent="copyMaterial">
                            <i class="fa fa-copy"></i> Copy</b-button>
                        </button>
                    </b-button-group>
                </b-button-toolbar>
            </div>

        </div>
        <b-alert :show="dismissCountDown"
                 class="alert-edit-recipe-card"
                 variant="info"
                 dismissible
                 @dismissed="dismissCountDown=0"
                 @dismiss-count-down="countDownChanged">
            <i class='fa fa-save'></i> {{ alertMessage }}
        </b-alert>
        <b-alert v-if="apiError" show variant="danger">
            API Error: {{ apiError.message }}
        </b-alert>
        <b-alert v-if="serverError" show variant="danger">
            {{ serverError }}
        </b-alert>
        <div class="load-container load7" v-if="isProcessing">
            <div class="loader">Loading...</div>
        </div>
        <div v-if="hasUmf && displayType === 'umf'" class="d-flex">
            <div>
                <umf-traditional-notation
                        :umfAnalysis="umfAnalysis"
                        :showSimpleLegend="true"
                        size="m">
                </umf-traditional-notation>
            </div>
        </div>
        <div v-if="hasUmf && displayType === 'umf'" class="d-flex">
            <div class="ratios">
                R<sub>2</sub>O:RO
                <span class="badge">
                    <span class="oxide-colors-Na2O">
                        {{ Number(umfAnalysis.getR2OTotal()).toFixed(2) }}
                    </span>
                    :
                    <span class="oxide-colors-CaO">
                        {{ Number(umfAnalysis.getROTotal()).toFixed(2) }}
                    </span>
                </span>
            </div>
            <div class="ratios ml-2">
                SiO<sub>2</sub>:Al<sub>2</sub>O<sub>3</sub>
                <span class="badge">
                    {{ Number(umfAnalysis.getSiO2Al2O3Ratio()).toFixed(2) }}
                </span>
            </div>
        </div>
        <div v-if="displayType === 'percentMol'"
             class="table-responsive">
            <component-table
                    :material="material"
                    :isFormula="true">
            </component-table>
        </div>
        <div v-if="displayType === 'percent'"
             class="table-responsive">
            <component-table
                    :material="material"
                    :isFormula="false">
            </component-table>
        </div>

        <div class="d-flex bd-highlight">
            <div class="p-2 flex-grow-1">Material</div>
            <div class="p-2">Amount</div>
            <div class="p-2">Add</div>
        </div>

        <div v-for="(fieldArray, index) in materialFieldsId"
             v-if="index < numVisibleRows"
             class="d-flex">

                <div class="flex-grow-1 pr-2">
                    <div class="multiselect-container">
                        <multiselect
                            :id="index + '_name'"
                            :options="selectMaterials"
                            v-model="materialFieldsId[index]"
                            @input="focusAmountInput(index)"
                            :multiple="false"
                            key="value"
                            label="label"
                            selectLabel=""
                            placeholder="Material"
                            class="edit-recipe-multiselect"
                        ></multiselect>
                    </div>
                </div>

                <div class="pr-2 amount-container">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <button style="min-width: 22px; max-width: 22px; margin: 0; padding: 0;"
                                    class="btn btn-sm btn-increment"
                                    type="button"
                                    tabindex="-1"
                                    @click.stop.prevent="decrementAmount(index)">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                        <input :id="index + '_amount'"
                               v-model="materialFieldsAmount[index]"
                               :ref="'amountInputRef' + index"
                               type="number"
                               inputmode="numeric"
                               size="5"
                               min="0"
                               placeholder="%"
                               v-focus="index === focused"
                               @focus="focused = index"
                               v-on:blur.native="unfocusAll"
                               @change="focused = index"
                               @input="updateMaterial"
                               class="form-control edit-recipe-amount-input float-left">
                        <div class="input-group-append">
                            <button style="min-width: 22px; max-width: 22px; margin: 0; padding: 0;"
                                    class="btn btn-sm btn-increment"
                                    type="button"
                                    tabindex="-1"
                                    @click.stop.prevent="incrementAmount(index)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="additional_container">
                    <b-form-checkbox id="index + '_add'"
                                     v-model="materialFieldsIsAdditional[index]"
                                     @input="updateMaterial"
                                     class="add-check"
                                     plain />
                </div>
        </div>
        <div class="d-flex justify-content-end">
            <div class="pr-2">
                <button class="btn btn-sm btn-secondary btn-set-100"
                        @click.prevent="setTo100">
                    Set 100%
                </button>
            </div>
            <div class="pr-4">
                <input v-model="subtotal"
                       placeholder="Total"
                       type="number"
                       inputmode="numeric"
                       disabled
                       class="form-control edit-recipe-subtotal">
            </div>
        </div>
        <div class="d-flex mt-2">
            <b-button-toolbar>
                <b-button-group size="sm" class="mx-1">
                    <b-button v-if="originalMaterial"
                              variant="neutral"
                              @click.prevent="resetRecipe"><i class="fa fa-refresh"></i> Reset</b-button>
                    <b-button variant="neutral"
                              @click.prevent="cancelRecipeCard"><i class="fa fa-times"></i> Remove</b-button>
                    <b-button variant="neutral"
                              @click.prevent="checkForDuplicates"><i class="fa fa-search"></i> Search</b-button>
                </b-button-group>
            </b-button-toolbar>
        </div>
    </b-card>
</template>

<script>
  import Material from 'ceramicscalc-js/src/material/Material'
  import UmfTraditionalNotation from '../analysis/UmfTraditionalNotation.vue'
  import ComponentTable from '../analysis/ComponentTable.vue'
  import Multiselect from 'vue-multiselect';
  import { focus } from 'vue-focus'

  export default {
    name: 'EditRecipeCard',
    components: {
      Multiselect,
      UmfTraditionalNotation,
      ComponentTable
    },
    props: {
      material: {
        type: Object,
        default: null
      },
      selectMaterials: {
        type: Array,
        default: []
      },
      lookupMaterialLibrary: {
        type: Object,
        default: null
      },
      displayType: {
        type: String,
        default: 'umf'
      }
    },
    directives: { focus: focus },
    data() {
      return {
        originalMaterial: null,
        materialFieldsId: [],
        materialFieldsAmount: [],
        materialFieldsIsAdditional: [],
        subtotal: 0,
        maximumRowNumber: 30,
        minimumVisibleRows: 5,
        focused: false,
        newName: '',
        apiError: null,
        serverError: null,
        alertMessage: null,
        isProcessing: false,
        dismissSecs: 10,
        dismissCountDown: 0
      }
    },

    mounted() {
      if (this.material) {
        this.originalMaterial = this.material.clone();
        this.resetMaterialFields();
        this.updateMaterial();
      }
    },

    watch: {
      material: function (val) {
        console.log('material changed');
        // If the underlying material changes, make sure we reset the component:
        if (this.material) {
          this.originalMaterial = this.material.clone();
          this.resetMaterialFields();
          this.updateMaterial();
        }
      }
    },

    computed : {
      numVisibleRows: function() {
        var numVisibleRows = 0;
        if (this.isLoaded) {
          for (var i = 0; i < this.materialFieldsId.length; i++) {
            if ((!this.materialFieldsId[i] || !this.materialFieldsId[i].value)
              && !this.materialFieldsAmount[i]
              && !this.materialFieldsIsAdditional[i]
            ) {
              numVisibleRows = i + 1;
              break;
            }
          }
          if (numVisibleRows > this.materialFieldsId.length) {
            return this.materialFieldsId.length;
          }
          if (numVisibleRows < this.minimumVisibleRows) {
            if (this.minimumVisibleRows <= this.materialFieldsId.length) {
              return this.minimumVisibleRows;
            }
            return this.materialFieldsId.length;
          }
        }
        return numVisibleRows;
      },
      umfAnalysis: function() {
        if (this.material) {
          return this.material.getROR2OUnityFormulaAnalysis();
        }
        return null;
      },
      hasUmf: function () {
        if (this.umfAnalysis &&
          (this.umfAnalysis.getR2OTotal() > 0 ||
          this.umfAnalysis.getROTotal() > 0)) {
          return true
        }
        return false
      }
    },
    methods: {

      isLoaded: function() {
        if (this.selectMaterials.length > 0 &&
            this.lookupMaterialLibrary &&
            this.material) {
          return true;
        }
        return false;
      },

      setSubtotal: function() {
        var subtotal = 0.0;

        for (var i = 0; i < this.materialFieldsId.length; i++) {
          if (this.materialFieldsId[i] && this.materialFieldsId[i].value
            && this.materialFieldsAmount[i] > 0
            && !isNaN(this.materialFieldsAmount[i])
          ) {
            subtotal += parseFloat(this.materialFieldsAmount[i]);
          }
        }

        this.subtotal = Number(subtotal).toFixed(2);
      },

      incrementAmount: function (i) {
        if (this.materialFieldsId[i]) {
          let originalValue = parseFloat(this.materialFieldsAmount[i])
          if (!originalValue) {
            originalValue = 0
          }
          //this.materialFieldsAmount[i] = originalValue + 1
          Vue.set(this.materialFieldsAmount, i, this.addValue(originalValue, 1));
          this.updateMaterial()
          this.$nextTick(() => this.$refs['amountInputRef' + i][0].select());
        }
      },

      decrementAmount: function (i) {
        let originalValue = parseFloat(this.materialFieldsAmount[i])
        if (originalValue > 0 && this.materialFieldsId[i]) {
          //this.materialFieldsAmount[i] = originalValue - 1;
          Vue.set(this.materialFieldsAmount, i, this.subtractValue(originalValue, 1));
          this.updateMaterial()
        }
        this.$nextTick(() => this.$refs['amountInputRef' + i][0].select());
      },

      setTo100: function() {
        if (this.isLoaded) {
          var subtotal = 0.0;
          for (var i = 0; i < this.materialFieldsId.length; i++) {
            if (!isNaN(this.materialFieldsAmount[i])
              && Number(this.materialFieldsAmount[i]) > 0
              && !this.materialFieldsIsAdditional[i]) {
              subtotal += parseFloat(this.materialFieldsAmount[i]);
            }
          }
          if (subtotal > 0) {
            for (var i = 0; i < this.materialFieldsId.length; i++) {
              var newVal = Math.round(this.materialFieldsAmount[i] / subtotal * 10000) / 100;
              if (newVal > 0) {
                this.materialFieldsAmount[i] = newVal;
              }
              else {
                this.materialFieldsAmount[i] = null;
              }
            }
          }
          this.setSubtotal();
        }
      },

      resetMaterialFields: function() {
        this.materialFieldsId = [];
        this.materialFieldsAmount = [];
        this.materialFieldsIsAdditional = [];

        if (this.originalMaterial) {
          for (var i = 0; i < this.originalMaterial.materialComponents.length; i++) {
            this.materialFieldsId.push({value: this.originalMaterial.materialComponents[i].material.id, label: this.originalMaterial.materialComponents[i].material.name});
            this.materialFieldsAmount.push(this.originalMaterial.materialComponents[i].amount);
            this.materialFieldsIsAdditional.push(this.originalMaterial.materialComponents[i].isAdditional);
          }
        }
        for (var i = 0; i < this.maximumRowNumber; i++) {
          this.materialFieldsId.push({value: null, label: ''});
          this.materialFieldsAmount.push(null);
          this.materialFieldsIsAdditional.push(false);
        }
      },

      resetRecipe: function() {
        this.resetMaterialFields();
        this.updateMaterial();
      },


      focusAmountInput (index) {
        this.focused = index
        this.updateMaterial()
      },


      updateMaterial () {
        this.material.removeAllMaterialComponents();

        for (var i = 0; i < this.materialFieldsId.length; i++) {
          if (this.materialFieldsId[i] && this.materialFieldsId[i].value &&
            this.materialFieldsAmount[i] > 0) {
            var materialObj = Material.createFromJson(
              this.lookupMaterialLibrary[this.materialFieldsId[i].value]
            );
            this.material.addMaterialComponent(
              materialObj,
              this.materialFieldsAmount[i],
              this.materialFieldsIsAdditional[i]
            );
          }
        }
        this.setSubtotal()
        this.$emit('materialUpdated');
      },

      store: function () {
        if (!this.isLoaded) {}
        this.$emit('isProcessing');
        this.isProcessing = true;

        var form = {
          materialComponents: []
        }

        for (var i = 0; i < this.materialFieldsId.length; i++) {
          if (this.materialFieldsId[i] && this.materialFieldsId[i].value
            && !isNaN(this.materialFieldsAmount[i]
              && this.materialFieldsAmount[i] > 0)
          ) {
            form.materialComponents.push({
              componentMaterialId: Number(this.materialFieldsId[i].value),
              percentageAmount: Number(this.materialFieldsAmount[i]),
              isAdditional: Boolean(this.materialFieldsIsAdditional[i])
            })
          }
        }

        if (this.material && this.material.id && this.material.id > 0) {
          // We are updating the original material
          form._method = 'PATCH';
          // form.materialComponents = JSON.stringify(form.materialComponents)
          Vue.axios.post(Vue.axios.defaults.baseURL + '/materialmaterials/' + this.material.id, form)
            .then((response) => {
            this.isProcessing = false;
            if (response.data.error) {
              // error
              this.apiError = response.data.error;
              console.log(this.apiError);
            } else {
              this.alertMessage = "Recipe Updated";
              this.dismissCountDown = this.dismissSecs;
              this.$emit('updatedRecipeComponents');
            }
          })
          .catch(response => {
            this.serverError = response;
            this.alertMessage = "Error: " + response;
            this.dismissCountDown = this.dismissSecs;
            this.isProcessing = false;
            console.log('UPDATE ERROR')
            console.log(response.data)
          })
        }
        else {
          // Create a new recipe
          if (form.materialComponents.length === 0) {
            // Don't create a new material that has no components
            // TODO: Add warning message
            this.isProcessing = false
            return
          }

          form.newName = this.material.name;
          if ('originalId' in this.material && this.material.originalId > 0) {
            form.originalId = this.material.originalId;
          }
          console.log('saving with original id of : ' + this.material.originalId);

          Vue.axios.post(Vue.axios.defaults.baseURL + '/materialmaterials/', form)
            .then((response) => {
              this.isProcessing = false;
              if (response.data.error) {
                // error
                this.apiError = response.data.error
              } else {
                // Unhighlight this material's id (it's been replaced):
                this.unhighlightMaterial(this.material.id);
                this.$emit('updatedMaterialId', {'originalId': this.material.id, 'newId': response.data.data.id});
                this.alertMessage = "Recipe Saved";
                this.dismissCountDown = this.dismissSecs;
              }
            })
            .catch(response => {
              this.serverError = response;
              this.alertMessage = "Error: " + response;
              this.dismissCountDown = this.dismissSecs;
              this.isProcessing = false
              console.log('UPDATE ERROR')
              console.log(response.data)
            });
        }
      },

      checkForDuplicates () {
        this.$emit('checkForDuplicates');
      },

      copyMaterial () {
        this.$emit('copyMaterial')
      },

      cancelRecipeCard: function() {
        this.materialFieldsId = [];
        this.materialFieldsAmount = [];
        this.materialFieldsIsAdditional = [];
        this.$emit('cancelRecipeCard');
      },

      unfocusAll () {
        this.focused = false;
        console.log('focusme');
      },

      countDownChanged (dismissCountDown) {
        this.dismissCountDown = dismissCountDown
      },

      highlightMaterial: function (id) {
        this.$emit('highlightMaterial', id);
      },

      unhighlightMaterial: function (id) {
        this.$emit('unhighlightMaterial', id);
      },

      countDecimals: function (value) {
        if(Math.floor(value) === value) return 0;
        return value.toString().split(".")[1].length || 0;
      },

      subtractValue: function (left, right) {
        let numDecimals = this.countDecimals(left);
        let result = left - right;
        if (numDecimals) {
          left = left * numDecimals * 10;
          right = right * numDecimals * 10;
          result = (left - right) / (numDecimals * 10);
        }
        if (result < 0) { return 0; }
        return result.toFixed(numDecimals);
      },

      addValue: function (left, right) {
        let numDecimals = this.countDecimals(left);
        let result = left + right;
        if (numDecimals) {
          left = left * numDecimals * 10;
          right = right * numDecimals * 10;
          result = (left + right) / (numDecimals * 10);
        }
        if (result < 0) { return 0; }
        return result.toFixed(numDecimals);
      }



    }
  }
</script>

<style>
    .btn-toolbar .btn-group .btn {
        margin: 0;
    }

    .btn-recipe-action {
        margin-top: 0;
    }
    .edit-recipe-components-card .btn-toolbar {
        margin: 0;

    }
    .edit-recipe-components-card .card-body {
        padding: 10px 10px;
    }
    .edit-recipe-components-card .card-body .ratios {
        margin-top: 4px;
        font-size: 14px;
    }
    .edit-recipe-components-card .card-body .ratios .badge {
        background-color: #efefef;
        font-size: 14px;
        padding: 4px;
        margin: 2px;
    }
    .edit-recipe-components-card .umf-traditional {
        margin: 0;
        font-size: 14px;
        line-height: 18px;
    }
    .edit-recipe-components-card .umf-traditional thead tr th {
        font-size: 12px;
    }
    .edit-recipe-components-card .umf-traditional tbody tr td.bracket {
        padding: 0 4px;
    }

    .edit-recipe-components-card .btn-increment {
        background-color: #cccccc;
    }

    .edit-recipe-components-card .btn-increment:hover,
    .edit-recipe-components-card .btn-increment:focus,
    .edit-recipe-components-card .btn-increment:active {
        background-color: #bbbbbb;
    }

    .edit-recipe-amount-input {
        font-size: 14px;
        padding: 2px 5px;
        width: 68px;
        max-width: 68px;
        height: 32px;
    }
    .btn-set-100 {
        margin: 0;
    }
    .edit-recipe-subtotal {
        font-size: 14px;
        padding: 2px 5px;
        width: 110px;
        max-width: 110px;
        height: 32px;
        margin: 0;
    }
    .edit-recipe-multiselect {
        font-size: 14px;
        line-height: 14px;
    }
    .edit-recipe-multiselect input {
        font-size: 14px;
        line-height: 14px;
    }
    .amount-container {
        min-width: 100px;
    }
    .add-check {
        margin-right: 0;
    }
    .alert-edit-recipe-card {
        padding: 5px;
    }
    .alert-edit-recipe-card button {
        margin-top: 8px;
    }
</style>