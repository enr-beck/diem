(function($)
{

  $.widget('ui.dmFrontPageAddForm', {
  
    _init: function()
    {
      this.autoSlug = true;
      
      this.form();
    },
    
    form: function()
    {
      var self = this;
			
			self.element.dmFrontForm();
      
      self.$name = $('input#dm_page_name', self.element);
      
      self.$parent = $('select#dm_page_parent_id', self.element);
      
      self.$slug = $('input#dm_page_slug', self.element).attr('disabled', self.autoSlug);
      
      self.parentSlugs = window["eval"]("(" + $('div.parent_slugs', self.element).text() + ")");
      
      self.$form = $('form', self.element).dmAjaxForm({
        beforeSubmit: function(data)
        {
          self.element.block();
        },
        success: function(data)
        {
          if (data.match(/\_\_DM\_SPLIT\_\_/)) 
          {
            self.element.dialog('close');
            $('body').block();
            location.href = data.split(/\_\_DM\_SPLIT\_\_/)[1];
            return;
          }
          self.element.html(data);
          self.form();
        }
      });
      
      self.$parent.bind('change', function()
      {
        self.changeSlug()
      });
      
      self.$name.bind('keyup', function()
      {
        self.changeSlug()
      });
      
      self.$slug.bind('keyup', function()
      {
				self.$slug.val(self.slugify(self.$slug.val()));
        self.autoSlug = false;
      });
      
      self.changeSlug();
    },
    
    changeSlug: function()
    {
      if (!this.autoSlug) 
      {
        return;
      }
      
      var self = this, parentSlug = self.parentSlugs[self.$parent.val()], name = self.$name.val();
      
      self.$slug.val(self.slugify(parentSlug ? parentSlug + '/' + name : name));
      
      if (self.$slug.attr('disabled') && name) 
      {
        self.$slug.attr('disabled', false);
      }
    },
    
    slugify: function(str)
    {
//      str = str.replace(/^\s+|\s+$/g, ''); // trim
      str = str.toLowerCase();
			
      // remove accents, swap ñ for n, etc
      var from = "àáäâèéëêìíïîòóöôùúüûñç·_,:;";
      var to = "aaaaeeeeiiiioooouuuunc-----";
      for (var i = 0, l = from.length; i < l; i++) 
      {
        str = str.replace(new RegExp(from[i], "g"), to[i]);
      }
      
      str = str.replace(/\s+|-{2,}/g, '-').replace(/[^a-zA-Z0-9-/]/g, '');
      return str;
    }
    
  });
  
})(jQuery);